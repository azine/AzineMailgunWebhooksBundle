<?php


namespace Azine\MailgunWebhooksBundle\Services;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Azine\MailgunWebhooksBundle\Entity\EmailTrafficStatistics;
use Doctrine\ORM\EntityManager;

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
     * @var string
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
     * @var EntityManager
     */
    private $entityManager;

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
     * @param EntityManager $entityManager
     * @param int $sendNotificationsInterval
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
        EntityManager $entityManager,
        $sendNotificationsInterval
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->fromEmail = $fromEmail;
        $this->ticketId = $ticketId;
        $this->ticketSubject = $ticketSubject;
        $this->ticketMessage = $ticketMessage;
        $this->spamAlertsRecipientEmail = $spamAlertsRecipientEmail;
        $this->entityManager = $entityManager;
        $this->sendNotificationsInterval = $sendNotificationsInterval;
    }

    /**
     * @param string $eventId
     * @throws \Exception
     * @return int $messagesSent
     */
    public function  sendSpamComplaintNotification($eventId)
    {
        $messagesSent = 0;
        $failedRecipients = [];

        /** @var \Swift_Message $message */
        $message = $this->mailer->createMessage();
        $message->setTo($this->spamAlertsRecipientEmail)
            ->setFrom($this->fromEmail)
            ->setSubject($this->translator->trans('notification.spam_complaint_received'))
            ->setBody(
                $this->twig->render('@AzineMailgunWebhooks/Email/notification.html.twig', array('eventId' => $eventId, 'ticketId' => $this->ticketId)),
                'text/html'
            );

        $lastSpamReport = $this->entityManager->getRepository(EmailTrafficStatistics::class)
            ->getLastByAction(EmailTrafficStatistics::SPAM_ALERT_SENT);

        if($lastSpamReport instanceof EmailTrafficStatistics) {

            $time = new \DateTime();
            $timeDiff = $time->diff($lastSpamReport->getCreated());

            if($timeDiff->i > $this->sendNotificationsInterval) {

                $messagesSent = $this->mailer->send($message, $failedRecipients);
            }

        }else{

            $messagesSent = $this->mailer->send($message, $failedRecipients);
        }

        if($messagesSent > 0) {

            $spamAlert = new EmailTrafficStatistics();
            $spamAlert->setAction(EmailTrafficStatistics::SPAM_ALERT_SENT);
            $this->entityManager->persist($spamAlert);
            $this->entityManager->flush($spamAlert);
            $this->entityManager->clear();
        }

        if($messagesSent == 0 && !empty($failedRecipients)){

            throw new \Exception('Tried to send notification about spam complaint but no messages were sent');
        }
        return $messagesSent;
    }
}