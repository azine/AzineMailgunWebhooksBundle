<?php

namespace Azine\MailgunWebhooksBundle\Command;

use Azine\MailgunWebhooksBundle\Entity\HetrixToolsBlacklistResponseNotification;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Checks if the last ip address from MailgunEvent entity is in blacklist.
 */
class CheckIpAddressIsBlacklistedCommand extends Command
{
    const NO_VALID_RESPONSE_FROM_HETRIX = 'No valid response from Hetrixtools service, try later.';
    const BLACKLIST_REPORT_WAS_SENT = 'Blacklist report was sent.';
    const BLACKLIST_REPORT_IS_SAME_AS_PREVIOUS = 'Blacklist report contains the same info as the last report that was sent.';
    const IP_IS_NOT_BLACKLISTED = 'Ip is not blacklisted.';
    const STARTING_RETRY = 'Initiating retry of the checking command. Tries left: ';

    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'mailgun:check-ip-in-blacklist';

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var AzineMailgunHetrixtoolsService
     */
    private $hetrixtoolsService;

    /**
     * @var AzineMailgunMailerService
     */
    private $azineMailgunService;

    /**
     * @var string
     */
    private $kernelEnvironment;

    /**
     * @var int
     */
    private $muteDays;

    /**
     * CheckIpAddressIsBlacklistedCommand constructor.
     *
     * @param ManagerRegistry                $managerRegistry
     * @param AzineMailgunHetrixtoolsService $hetrixtoolsService
     * @param AzineMailgunMailerService      $azineMailgunService
     * @param $environment
     */
    public function __construct(ManagerRegistry $managerRegistry, AzineMailgunHetrixtoolsService $hetrixtoolsService,
                                AzineMailgunMailerService $azineMailgunService, $environment, $muteDays)
    {
        $this->managerRegistry = $managerRegistry;
        $this->hetrixtoolsService = $hetrixtoolsService;
        $this->azineMailgunService = $azineMailgunService;
        $this->kernelEnvironment = $environment;
        $this->muteDays = $muteDays;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('Checks if the last sending IP address from MailgunEvent entity is in blacklist')
            ->addArgument('numberOfAttempts',
                InputArgument::OPTIONAL,
                'Number of retry attempts in case if there were no response from hetrixtools or the process of checking blacklist was still in progress');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->managerRegistry->getManager();
        /** @var MailgunEventRepository $eventRepository */
        $eventRepository = $manager->getRepository(MailgunEvent::class);
        $ipAddressData = $eventRepository->getLastKnownSenderIpData();
        $ipAddress = null;

        if (isset($ipAddressData['ip'])) {
            $ipAddress = $ipAddressData['ip'];
            $sendDateTime = new \DateTime();
            $sendDateTime->setTimestamp($ipAddressData['timestamp']);
        }
        $numberOfAttempts = $input->getArgument('numberOfAttempts');

        try {
            $response = $this->hetrixtoolsService->checkIpAddressInBlacklist($ipAddress);
        } catch (\InvalidArgumentException $ex) {
            $output->write(self::NO_VALID_RESPONSE_FROM_HETRIX);

            if (null != $numberOfAttempts && $numberOfAttempts > 0) {
                $output->write(self::STARTING_RETRY.$numberOfAttempts);
                $this->retry($numberOfAttempts);
            }

            return -1;
        }

        if (HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS == $response->status) {
            if (0 == $response->blacklisted_count) {
                $output->write(self::IP_IS_NOT_BLACKLISTED." ($ipAddress)");
            } elseif ($this->muteNotification($response)) {
                $output->write(self::BLACKLIST_REPORT_IS_SAME_AS_PREVIOUS." ($ipAddress)");
            } else {
                try {
                    $messagesSent = $this->azineMailgunService->sendBlacklistNotification($response, $ipAddress, $sendDateTime);

                    if ($messagesSent > 0) {
                        $output->write(self::BLACKLIST_REPORT_WAS_SENT." ($ipAddress)");
                    }
                    if ($this->muteDays > 0) {
                        $blacklistResponseNotification = new HetrixToolsBlacklistResponseNotification();
                        $blacklistResponseNotification->setData($response);
                        $blacklistResponseNotification->setIp($ipAddress);
                        $blacklistResponseNotification->setDate($sendDateTime);
                        $blacklistResponseNotification->setIgnoreUntil(new \DateTime('+'.$this->muteDays.' days'));
                        $manager = $this->managerRegistry->getManager();
                        $manager->persist($blacklistResponseNotification);
                        $manager->flush();
                    }
                } catch (\Exception $e) {
                    $output->write($e->getMessage(), true);
                }
            }
        } elseif (HetrixtoolsServiceResponse::RESPONSE_STATUS_ERROR == $response->status) {
            $output->write($response->error_message);

            if (null != $numberOfAttempts && $numberOfAttempts > 0 && HetrixtoolsServiceResponse::BLACKLIST_CHECK_IN_PROGRESS == $response->error_message) {
                $output->write(self::STARTING_RETRY.$numberOfAttempts);
                $this->retry($numberOfAttempts);
            }

            return -1;
        }

        return 0;
    }

    private function retry($numberOfAttempts)
    {
        --$numberOfAttempts;

        $cmd = sprintf(
            '%s/console %s %s --env=%s',
            static::$defaultName,
            $numberOfAttempts,
            $this->kernelEnvironment
        );

        $process = new Process($cmd);
        $process->start();
    }

    private function muteNotification($response)
    {
        if (0 == $this->muteDays) {
            // don't mute if feature is disabled
            return false;
        }

        $ip = substr($response->links['api_report_link'], strrpos($response->links['api_report_link'], '/', -3) + 1, -1);
        $responseRepository = $this->managerRegistry->getManager()->getRepository(HetrixToolsBlacklistResponseNotification::class);
        /** @var HetrixToolsBlacklistResponseNotification $lastNotifiedResponse */
        $lastNotifiedResponses = $responseRepository->findBy(array('ip' => $ip), array('ignoreUntil' => 'desc'));

        if (0 == sizeof($lastNotifiedResponses)) {
            // don't mute if this is the first check for this ip
            return false;
        }

        if ($lastNotifiedResponses[0]->getIgnoreUntil() < new \DateTime()) {
            // don't mute if the last notification it too long ago
            return false;
        }

        $newBlackLists = $response->blacklisted_on;
        $oldBlacklists = $lastNotifiedResponses[0]->getData()['blacklisted_on'];

        $blacklistsUnchanged = is_array($newBlackLists) && is_array($oldBlacklists)
            && count($newBlackLists) == count($oldBlacklists)
            && $newBlackLists == $oldBlacklists;

        return $blacklistsUnchanged;
    }
}
