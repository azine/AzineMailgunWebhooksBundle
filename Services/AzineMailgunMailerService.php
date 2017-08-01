<?php


namespace Azine\MailgunWebhooksBundle\Services;

use Monolog\Logger;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        $adminUserEmail
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
    }

    public function  sendSpamComplaintNotification($eventId)
    {
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
            
            $this->mailer->send($message);
        } else {
            $this->logger->warning('Tried to send notification about spam complaint but adminUserEmail is invalid: ' . json_encode($emailValidityErrors));
        }
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