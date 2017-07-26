<?php


namespace Azine\MailgunWebhooksBundle\Services;

class AzineMailgunMailerService
{
    private $mailer;
    private $ticketId;
    private $ticketSubject;
    private $ticketMessage;
    private $adminUserEmail;
    
    public function __construct(
        \Swift_Mailer $mailer,
        $ticketId,
        $ticketSubject,
        $ticketMessage,
        $adminUserEmail
    ) {
        $this->mailer = $mailer;
        $this->ticketId = $ticketId;
        $this->ticketSubject = $ticketSubject;
        $this->ticketMessage = $ticketMessage;
        $this->adminUserEmail = $adminUserEmail;
    }

    public function sendNewEmailRequest()
    {
        return;
    }
}