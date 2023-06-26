<?php

namespace Azine\MailgunWebhooksBundle\Services;

use Azine\MailgunWebhooksBundle\Entity\EmailTrafficStatistics;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Translation\TranslatorInterface;

class AzineMailgunMailerService
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var mixed either string email or array(email => displayName)
     */
    private $fromEmail;

    /**
     * @var string
     */
    private $ticketId;

    /**
     * @var string
     */
    private $ticketSubject;

    /**
     * @var string
     */
    private $ticketMessage;

    /**
     * @var string
     */
    private $spamAlertsRecipientEmail;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var int
     */
    private $sendNotificationsInterval;

    /**
     * AzineMailgunMailerService constructor.
     *
     * @param \Swift_Mailer $mailer
     * @param \Twig_Environment $twig
     * @param TranslatorInterface $translator
     * @param string $fromEmail
     * @param string $ticketId
     * @param string $ticketSubject
     * @param string $ticketMessage
     * @param string $spamAlertsRecipientEmail
     * @param ManagerRegistry $managerRegistry
     * @param int $sendNotificationsInterval in Seconds
     */
    public function __construct(
        \Swift_Mailer $mailer,
        \Twig_Environment $twig,
        TranslatorInterface $translator,
        $fromEmail,
        $ticketId,
        $ticketSubject,
        $ticketMessage,
        $spamAlertsRecipientEmail,
        ManagerRegistry $managerRegistry,
        $sendNotificationsInterval
    )
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->fromEmail = $fromEmail;
        $this->ticketId = $ticketId;
        $this->ticketSubject = $ticketSubject;
        $this->ticketMessage = $ticketMessage;
        $this->spamAlertsRecipientEmail = $spamAlertsRecipientEmail;
        $this->managerRegistry = $managerRegistry;
        $this->sendNotificationsInterval = $sendNotificationsInterval;
    }

    /**
     * @param string $eventId
     *
     * @return int $messagesSent
     * @throws \Exception
     *
     */
    public function sendSpamComplaintNotification($eventId)
    {
        $messagesSent = 0;
        $failedRecipients = array();

        $templateVars = array();
        $templateVars['eventId'] = $eventId;
        $templateVars['ticketId'] = $this->ticketId;


        /** @var \Swift_Message $message */
        $message = $this->mailer->createMessage();
        $message->setTo($this->spamAlertsRecipientEmail)
            ->setFrom($this->fromEmail)
            ->setSubject($this->translator->trans('notification.spam_complaint_received'))
            ->setBody($this->twig->render('@AzineMailgunWebhooks/Email/notification.html.twig', $templateVars), 'text/html')
            ->setBody($this->twig->render('@AzineMailgunWebhooks/Email/notification.txt.twig', $templateVars), 'text/plain');

        $lastSpamReport = $this->managerRegistry->getManager()->getRepository(EmailTrafficStatistics::class)
            ->getLastByAction(EmailTrafficStatistics::SPAM_ALERT_SENT);

        if ($lastSpamReport instanceof EmailTrafficStatistics) {
            $time = new \DateTime();
            $timeDiff = $time->diff($lastSpamReport->getCreated());

            if ($timeDiff->s > $this->sendNotificationsInterval) {
                $messagesSent = $this->mailer->send($message, $failedRecipients);
            }
        } else {
            $messagesSent = $this->mailer->send($message, $failedRecipients);
        }

        if ($messagesSent > 0) {
            $spamAlert = new EmailTrafficStatistics();
            $spamAlert->setAction(EmailTrafficStatistics::SPAM_ALERT_SENT);
            $manager = $this->managerRegistry->getManager();
            $manager->persist($spamAlert);
            $manager->flush($spamAlert);
            $manager->clear();
        }

        if (0 == $messagesSent && !empty($failedRecipients)) {
            throw new \Exception('Tried to send notification about spam complaint but no messages were sent');
        }

        return $messagesSent;
    }

    /**
     * @param HetrixtoolsServiceResponse $response
     * @param string $ipAddress
     * @param \DateTime
     *
     * @return int
     *
     * @throws \Exception
     */
    public function sendBlacklistNotification(HetrixtoolsServiceResponse $response, $ipAddress, \DateTime $sendDateTime)
    {
        $sendDateTime = (new \DateTime())->format('Y-m-d H:i:s');
        $failedRecipients = array();

        $templateVars = array();
        $templateVars['response'] = $response;
        $templateVars['ipAddress'] = $ipAddress;
        $templateVars['sendDateTime'] = $sendDateTime;

        /** @var \Swift_Message $message */
        $message = $this->mailer->createMessage();
        $message->setTo($this->spamAlertsRecipientEmail)
            ->setFrom($this->fromEmail)
            ->setSubject($this->translator->trans('notification.blacklist_received'))
            ->setBody($this->twig->render('@AzineMailgunWebhooks/Email/blacklistNotification.html.twig', $templateVars), 'text/html')
            ->addPart($this->twig->render('@AzineMailgunWebhooks/Email/blacklistNotification.txt.twig', $templateVars), 'text/plain');

        $messagesSent = $this->mailer->send($message, $failedRecipients);

        if (0 == $messagesSent && !empty($failedRecipients)) {
            throw new \Exception('Tried to send notification about ip is blacklisted but no messages were sent');
        }

        return $messagesSent;
    }

    public function sendErrorNotification(MailgunEvent $event) {
        $sendDateTime = (new \DateTime())->format('Y-m-d H:i:s');
        $originalSender = $this->extractValidEmail($event->getEventSummary()->getFromAddress());
        $eventRecipient = $this->extractValidEmail($event->getRecipient());

        $templateVars = array();
        $templateVars['mailgunEvent'] = $event;
        $templateVars['mailgunMessageSummary'] = $event->getEventSummary();
        $templateVars['recipient'] = array('displayName' => mailparse_rfc822_parse_addresses($event->getEventSummary()->getFromAddress())[0]['display']);

        /** @var \Swift_Message $message */
        $message = $this->mailer->createMessage();
        $message->setTo($originalSender)
            ->setFrom($this->fromEmail)
            ->setSubject($this->translator->trans('notification.email.delivery.to.%originalRecipient%.failed', ['%originalRecipient%' => $event->getRecipient()]))
            ->setBody($this->twig->render('@AzineMailgunWebhooks/Email/deliveryErrorNotification.html.twig', $templateVars), 'text/html')
            ->addPart($this->twig->render('@AzineMailgunWebhooks/Email/deliveryErrorNotification.txt.twig', $templateVars), 'text/plain');

        $messagesSent = $this->mailer->send($message, $failedRecipients);

        if (0 == $messagesSent && !empty($failedRecipients)) {
            throw new \Exception('Tried to send notification about email delivery error but no messages were sent');
        }

        return $messagesSent;
    }

    /**
     * @param $address
     * @return string RFC 2822, 3.6.2. compliant email address-array array('receiver@domain.org', 'other@domain.org' => 'A name')
     */
    private function extractValidEmail($address){
        $addressParts = mailparse_rfc822_parse_addresses($address);
        $emails = array();
        foreach ($addressParts as $next){
            $emails[$next['address']] = $next['display'];
        }
        return $emails;
    }

}
