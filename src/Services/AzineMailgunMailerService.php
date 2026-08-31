<?php

namespace Azine\MailgunWebhooksBundle\Services;

use Azine\MailgunWebhooksBundle\Entity\EmailTrafficStatistics;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class AzineMailgunMailerService
{
    /**
     * @param string|array<string|string> $fromEmail
     */
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly TranslatorInterface $translator,
        private readonly string|array $fromEmail,
        private readonly string $ticketId,
        private readonly string $ticketSubject,
        private readonly string $ticketMessage,
        private readonly string $spamAlertsRecipientEmail,
        private readonly ManagerRegistry $managerRegistry,
        private readonly int $sendNotificationsInterval,
    ) {
    }

    public function sendSpamComplaintNotification(string|int $eventId): int
    {
        $lastSpamReport = $this->managerRegistry
            ->getManager()
            ->getRepository(EmailTrafficStatistics::class)
            ->getLastByAction(EmailTrafficStatistics::SPAM_ALERT_SENT);

        if ($lastSpamReport instanceof EmailTrafficStatistics) {
            $elapsedSeconds = time() - $lastSpamReport->getCreated()->getTimestamp();
            if ($elapsedSeconds <= $this->sendNotificationsInterval) {
                return 0;
            }
        }

        $templateVariables = [
            'eventId' => $eventId,
            'ticketId' => $this->ticketId,
            'ticketSubject' => $this->ticketSubject,
            'ticketMessage' => $this->ticketMessage,
        ];

        $message = $this->createEmail()
            ->to(...$this->normalizeAddresses($this->spamAlertsRecipientEmail))
            ->subject($this->translator->trans('notification.spam_complaint_received'))
            ->html($this->twig->render('@AzineMailgunWebhooks/Email/notification.html.twig', $templateVariables))
            ->text($this->twig->render('@AzineMailgunWebhooks/Email/notification.txt.twig', $templateVariables));

        $this->mailer->send($message);

        $spamAlert = new EmailTrafficStatistics();
        $spamAlert->setAction(EmailTrafficStatistics::SPAM_ALERT_SENT);
        $manager = $this->managerRegistry->getManager();
        $manager->persist($spamAlert);
        $manager->flush();
        $manager->clear();

        return 1;
    }

    public function sendBlacklistNotification(
        HetrixtoolsServiceResponse $response,
        string $ipAddress,
        \DateTimeInterface $sendDateTime,
    ): int {
        $templateVariables = [
            'response' => $response,
            'ipAddress' => $ipAddress,
            'sendDateTime' => $sendDateTime->format('Y-m-d H:i:s'),
        ];

        $message = $this->createEmail()
            ->to(...$this->normalizeAddresses($this->spamAlertsRecipientEmail))
            ->subject($this->translator->trans('notification.blacklist_received'))
            ->html($this->twig->render('@AzineMailgunWebhooks/Email/blacklistNotification.html.twig', $templateVariables))
            ->text($this->twig->render('@AzineMailgunWebhooks/Email/blacklistNotification.txt.twig', $templateVariables));

        $this->mailer->send($message);

        return 1;
    }

    public function sendErrorNotification(MailgunEvent $event): int
    {
        $fromAddress = $event->getEventSummary()->getFromAddress();
        $originalSenders = $this->extractValidEmail($fromAddress);

        if ([] === $originalSenders) {
            throw new \InvalidArgumentException(sprintf('No valid sender address could be parsed from "%s".', $fromAddress));
        }

        $parsedSender = mailparse_rfc822_parse_addresses($fromAddress)[0] ?? [];
        $templateVariables = [
            'mailgunEvent' => $event,
            'mailgunMessageSummary' => $event->getEventSummary(),
            'recipient' => ['displayName' => (string) ($parsedSender['display'] ?? '')],
            'sendDateTime' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $message = $this->createEmail()
            ->to(...$originalSenders)
            ->subject($this->translator->trans(
                'notification.email.delivery.to.%originalRecipient%.failed',
                ['%originalRecipient%' => $event->getRecipient()],
            ))
            ->html($this->twig->render('@AzineMailgunWebhooks/Email/deliveryErrorNotification.html.twig', $templateVariables))
            ->text($this->twig->render('@AzineMailgunWebhooks/Email/deliveryErrorNotification.txt.twig', $templateVariables));

        $this->mailer->send($message);

        return 1;
    }

    private function createEmail(): Email
    {
        return (new Email())->from(...$this->normalizeAddresses($this->fromEmail));
    }

    /**
     * @param string|array<string|string> $addresses
     *
     * @return list<Address>
     */
    private function normalizeAddresses(string|array $addresses): array
    {
        if (is_string($addresses)) {
            return [new Address($addresses)];
        }

        $normalized = [];
        foreach ($addresses as $email => $name) {
            if (is_int($email)) {
                $normalized[] = new Address((string) $name);
                continue;
            }

            $normalized[] = new Address((string) $email, (string) $name);
        }

        return $normalized;
    }

    /**
     * @return list<Address>
     */
    private function extractValidEmail(string $address): array
    {
        $addresses = [];
        foreach (mailparse_rfc822_parse_addresses($address) as $parsedAddress) {
            $email = (string) ($parsedAddress['address'] ?? '');
            if ('' === $email || false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $addresses[] = new Address($email, (string) ($parsedAddress['display'] ?? ''));
        }

        return $addresses;
    }
}
