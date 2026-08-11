<?php

namespace Azine\MailgunWebhooksBundle\Command;

use Azine\MailgunWebhooksBundle\Entity\HetrixToolsBlacklistResponseNotification;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mailgun:check-ip-in-blacklist',
    description: 'Checks whether the most recently used Mailgun sending IP is blacklisted.',
)]
class CheckIpAddressIsBlacklistedCommand extends Command
{
    public const NO_VALID_RESPONSE_FROM_HETRIX = 'No valid response from Hetrixtools service, try later.';
    public const BLACKLIST_REPORT_WAS_SENT = 'Blacklist report was sent.';
    public const BLACKLIST_REPORT_IS_SAME_AS_PREVIOUS = 'Blacklist report contains the same info as the last report that was sent.';
    public const IP_IS_NOT_BLACKLISTED = 'Ip is not blacklisted.';
    public const STARTING_RETRY = 'Initiating retry of the checking command. Tries left: ';

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly AzineMailgunHetrixtoolsService $hetrixtoolsService,
        private readonly AzineMailgunMailerService $azineMailgunService,
        private readonly string $kernelEnvironment,
        private readonly int $muteDays,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'numberOfAttempts',
            InputArgument::OPTIONAL,
            'Number of retry attempts when Hetrixtools has no response or the blacklist check is still in progress.',
            0,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $manager = $this->managerRegistry->getManager();
        /** @var MailgunEventRepository $eventRepository */
        $eventRepository = $manager->getRepository(MailgunEvent::class);
        $ipAddressData = $eventRepository->getLastKnownSenderIpData();
        $ipAddress = $ipAddressData['ip'] ?? null;
        $sendDateTime = isset($ipAddressData['timestamp'])
            ? new \DateTimeImmutable('@'.(string) $ipAddressData['timestamp'])
            : new \DateTimeImmutable();
        $attemptsLeft = max(0, (int) $input->getArgument('numberOfAttempts'));

        while (true) {
            try {
                $response = $this->hetrixtoolsService->checkIpAddressInBlacklist($ipAddress);
            } catch (\InvalidArgumentException) {
                $output->writeln(self::NO_VALID_RESPONSE_FROM_HETRIX);

                if ($attemptsLeft > 0) {
                    $output->writeln(self::STARTING_RETRY.$attemptsLeft);
                    --$attemptsLeft;
                    continue;
                }

                return Command::FAILURE;
            }

            if (
                HetrixtoolsServiceResponse::RESPONSE_STATUS_ERROR === $response->status
                && HetrixtoolsServiceResponse::BLACKLIST_CHECK_IN_PROGRESS === $response->error_message
                && $attemptsLeft > 0
            ) {
                $output->writeln((string) $response->error_message);
                $output->writeln(self::STARTING_RETRY.$attemptsLeft);
                --$attemptsLeft;
                continue;
            }

            break;
        }

        if (HetrixtoolsServiceResponse::RESPONSE_STATUS_ERROR === $response->status) {
            $output->writeln((string) $response->error_message);

            return Command::FAILURE;
        }

        if (HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS !== $response->status) {
            $output->writeln(self::NO_VALID_RESPONSE_FROM_HETRIX);

            return Command::FAILURE;
        }

        if (0 === (int) $response->blacklisted_count) {
            $output->writeln(self::IP_IS_NOT_BLACKLISTED." ($ipAddress)");

            return Command::SUCCESS;
        }

        if ($this->muteNotification($response)) {
            $output->writeln(self::BLACKLIST_REPORT_IS_SAME_AS_PREVIOUS." ($ipAddress)");

            return Command::SUCCESS;
        }

        try {
            $messagesSent = $this->azineMailgunService->sendBlacklistNotification($response, (string) $ipAddress, $sendDateTime);

            if ($messagesSent > 0) {
                $output->writeln(self::BLACKLIST_REPORT_WAS_SENT." ($ipAddress)");

                if ($this->muteDays > 0) {
                    $blacklistResponseNotification = new HetrixToolsBlacklistResponseNotification();
                    $blacklistResponseNotification->setData($response);
                    $blacklistResponseNotification->setIp($ipAddress);
                    $blacklistResponseNotification->setDate(\DateTime::createFromImmutable($sendDateTime));
                    $blacklistResponseNotification->setIgnoreUntil(new \DateTime('+'.$this->muteDays.' days'));
                    $manager->persist($blacklistResponseNotification);
                    $manager->flush();
                }
            }
        } catch (\Throwable $exception) {
            $output->writeln($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function muteNotification(HetrixtoolsServiceResponse $response): bool
    {
        if (0 === $this->muteDays) {
            return false;
        }

        $apiReportLink = (string) ($response->links['api_report_link'] ?? '');
        $ip = substr($apiReportLink, strrpos($apiReportLink, '/', -3) + 1, -1);
        $responseRepository = $this->managerRegistry
            ->getManager()
            ->getRepository(HetrixToolsBlacklistResponseNotification::class);
        $lastNotifiedResponses = $responseRepository->findBy(['ip' => $ip], ['ignoreUntil' => 'desc']);

        if ([] === $lastNotifiedResponses) {
            return false;
        }

        /** @var HetrixToolsBlacklistResponseNotification $lastNotifiedResponse */
        $lastNotifiedResponse = $lastNotifiedResponses[0];
        if ($lastNotifiedResponse->getIgnoreUntil() < new \DateTime()) {
            return false;
        }

        $newBlacklists = $response->blacklisted_on;
        $oldBlacklists = $lastNotifiedResponse->getData()['blacklisted_on'] ?? null;

        return is_array($newBlacklists)
            && is_array($oldBlacklists)
            && count($newBlacklists) === count($oldBlacklists)
            && $newBlacklists === $oldBlacklists;
    }
}
