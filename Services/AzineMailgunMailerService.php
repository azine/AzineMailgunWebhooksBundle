<?php


namespace Azine\MailgunWebhooksBundle\Services;

use Monolog\Logger;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Azine\MailgunWebhooksBundle\Entity\EmailTrafficStatistics;

class AzineMailgunMailerService
{
    private $mailer;
    private $validator;
    private $logger;
    private $twig;
    private $translator;
    private $mailerUser;
    private $ticketId;
    private $ticketSubject;
    private $ticketMessage;
    private $adminUserEmail;
    private $entityManager;
    private $sendNotificationsInterval;

    public function __construct(
        \Swift_Mailer $mailer,
        ValidatorInterface $validator,
        Logger $logger,
        \Twig_Environment $twig,
        TranslatorInterface $translator,
        $mailerUser,
        $ticketId,
        $ticketSubject,
        $ticketMessage,
        $adminUserEmail,
        $entityManager,
        $sendNotificationsInterval
    ) {
        $this->mailer = $mailer;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->mailerUser = $mailerUser;
        $this->ticketId = $ticketId;
        $this->ticketSubject = $ticketSubject;
        $this->ticketMessage = $ticketMessage;
        $this->adminUserEmail = $adminUserEmail;
        $this->entityManager = $entityManager;
        $this->sendNotificationsInterval = $sendNotificationsInterval;
    }

    public function  sendSpamComplaintNotification($eventId)
    {
        $messagesSent = 0;
        $emailValidityErrors = $this->validateEmail($this->adminUserEmail);

        if (is_null($emailValidityErrors)) {
            /** @var \Swift_Message $message */
            $message = $this->mailer->createMessage();
            $message->setTo($this->adminUserEmail)
                ->setFrom($this->mailerUser)
                ->setSubject($this->translator->trans('notification.spam_complaint_received'))
                ->setBody(
                    $this->twig->render('@AzineMailgunWebhooks/Email/notification.html.twig', array('eventId' => $eventId, 'ticketId' => $this->ticketId)),
                    'text/html'
                );

            $lastSpamReport = $this->entityManager->getRepository(EmailTrafficStatistics::class)
                ->findOneBy(['action' => EmailTrafficStatistics::SPAM_ALERT_SENT],
                    ['created' => 'DESC']);


            if($lastSpamReport instanceof EmailTrafficStatistics) {

                $time = new \DateTime();
                $timeDiff = $time->diff($lastSpamReport->getCreated());

                if($timeDiff->i > $this->sendNotificationsInterval) {

                    $messagesSent = $this->mailer->send($message);
                }

            }else{

                $messagesSent = $this->mailer->send($message);
            }

            if($messagesSent > 0) {

                $spamAlert = new EmailTrafficStatistics();
                $spamAlert->setAction(EmailTrafficStatistics::SPAM_ALERT_SENT);
                $this->entityManager->persist($spamAlert);
                $this->entityManager->flush($spamAlert);
                $this->entityManager->clear();
            }
        } else {
            $this->logger->warning('Tried to send notification about spam complaint but adminUserEmail is invalid: ' . json_encode($emailValidityErrors));
        }

        return $messagesSent;
    }

    private function validateEmail($email)
    {
        $errors = $this->validator->validate(
            $email,
            array(
                new \Symfony\Component\Validator\Constraints\Email(),
                new \Symfony\Component\Validator\Constraints\NotBlank()
            )
        );

        if (count($errors) > 0) {
            return $errors;
        } else {
            return null;
        }
    }
}