<?php

namespace Azine\MailgunWebhooksBundle\Command;

use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService;
use Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Process\Process;

/**
 * Checks if the last ip address from MailgunEvent entity is in blacklist
 *
 */
class CheckIpAddressIsBlacklistedCommand extends ContainerAwareCommand
{
    const NO_VALID_RESPONSE_FROM_HETRIX = 'No valid response from Hetrixtools service, try later.';
    const BLACKLIST_REPORT_WAS_SENT = 'Blacklist report was sent.';
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
     * CheckIpAddressIsBlacklistedCommand constructor.
     * @param ManagerRegistry $managerRegistry
     * @param AzineMailgunHetrixtoolsService $hetrixtoolsService
     * @param AzineMailgunMailerService $azineMailgunService
     * @param $environment
     */
    public function __construct(ManagerRegistry $managerRegistry, AzineMailgunHetrixtoolsService $hetrixtoolsService,
                                AzineMailgunMailerService $azineMailgunService, $environment)
    {
        $this->managerRegistry = $managerRegistry;
        $this->hetrixtoolsService = $hetrixtoolsService;
        $this->azineMailgunService = $azineMailgunService;
        $this->kernelEnvironment = $environment;

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
        $eventRepository = $manager->getRepository('AzineMailgunWebhooksBundle:MailgunEvent');
        $ipAddress = $eventRepository->getLastKnownSenderIp();
        $numberOfAttempts = $input->getArgument("numberOfAttempts");

        try {
            $response = $this->hetrixtoolsService->checkIpAddressInBlacklist($ipAddress);
        } catch (\InvalidArgumentException $ex) {

            $output->write(self::NO_VALID_RESPONSE_FROM_HETRIX);

            if ($numberOfAttempts != null && $numberOfAttempts > 0 ) {
                $output->write(self::STARTING_RETRY . $numberOfAttempts);
                $this->retry($numberOfAttempts);
            }
            return false;
        }

        if ($response->status == HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS) {

            if ($response->blacklisted_count > 0) {

                try {

                    $messagesSent = $this->azineMailgunService->sendBlacklistNotification($response, $ipAddress);

                    if ($messagesSent > 0) {

                        $output->write(self::BLACKLIST_REPORT_WAS_SENT . " ($ipAddress)");
                    }
                } catch (\Exception $e) {

                    $output->write($e->getMessage(), true);
                }
            } else {

                $output->write(self::IP_IS_NOT_BLACKLISTED . " ($ipAddress)");
            }
        } elseif ($response->status == HetrixtoolsServiceResponse::RESPONSE_STATUS_ERROR) {

            $output->write($response->error_message);

            if ($numberOfAttempts != null && $numberOfAttempts > 0 && $response->error_message == HetrixtoolsServiceResponse::BLACKLIST_CHECK_IN_PROGRESS) {
                $output->write(self::STARTING_RETRY . $numberOfAttempts);
                $this->retry($numberOfAttempts);
            }
            return false;
        }
    }

    private function retry($numberOfAttempts)
    {
        $numberOfAttempts--;

        $cmd = sprintf(
            '%s/console %s %s --env=%s',
            static::$defaultName,
            $numberOfAttempts,
            $this->kernelEnvironment
        );

        $process = new Process($cmd);
        $process->start();
    }
}