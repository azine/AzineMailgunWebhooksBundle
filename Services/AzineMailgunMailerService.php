<?php


namespace Azine\MailgunWebhooksBundle\Services;

use Monolog\Logger;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AzineMailgunMailerService
{
    private $mailer;
    private $validator;
    private $logger;
    private $twig;
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
        $this->mailerUser = $mailerUser;
        $this->ticketId = $ticketId;
        $this->ticketSubject = $ticketSubject;
        $this->ticketMessage = $ticketMessage;
        $this->adminUserEmail = $adminUserEmail;
    }

    public function  sendSpamComplaintNotification($eventId)
    {
        if (is_null($this->validateEmail($this->adminUserEmail))) {            
            /** @var \Swift_Message $message */
            $message = $this->mailer->createMessage();
            $message->setTo($this->adminUserEmail)
                    ->setFrom($this->mailerUser)
                    ->setSubject('Spam complaint received')
                    ->setBody(
                        $this->twig->render('@AzineMailgunWebhooks/Email/notification.html.twig', array('eventId' => $eventId, 'ticketId' => $this->ticketId)),
                        'text/html'
                    );
            
            $this->mailer->send($message);
        } else {
            $errors = $this->validateEmail($this->adminUserEmail);
            $this->logger->warning('Tried to send notification about spam complaint but adminUserEmail is invalid: ' . json_encode($errors));
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