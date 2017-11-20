<?php
namespace Azine\MailgunWebhooksBundle\Command;

use Azine\MailgunWebhooksBundle\Services\AzineMailgunService;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService;
use Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManager;

/**
 * Checks if the last ip address from MailgunEvent entity is in blacklist
 *
 */
class CheckIpAddressIsBlacklistedCommand extends ContainerAwareCommand
{
    const NO_RESPONSE_FROM_HETRIX = 'No response from Hetrixtools service, try later.';
    const BLACKLIST_REPORT_WAS_SENT = 'Blacklist report was sent.';
    const IP_IS_NOT_BLACKLISTED = 'Ip is not blacklisted.';

    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'mailgun:check-ip-in-blacklist';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var AzineMailgunHetrixtoolsService
     */
    private $hetrixtoolsService;

    /**
     * @var AzineMailgunMailerService
     */
    private $azineMailgunService;


    public function __construct(EntityManager $entityManager, AzineMailgunHetrixtoolsService $hetrixtoolsService, AzineMailgunMailerService $azineMailgunService)
    {
        $this->entityManager = $entityManager;
        $this->hetrixtoolsService = $hetrixtoolsService;
        $this->azineMailgunService = $azineMailgunService;

        parent::__construct();

    }

    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('Checks if the last ip address from MailgunEvent entity is in blacklist');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventRepository = $this->entityManager->getRepository('AzineMailgunWebhooksBundle:MailgunEvent');
        $ipAddress = $eventRepository->getLastKnownSenderIp();

        $response = $this->hetrixtoolsService->checkIpAddressInBlacklist($ipAddress);

        if(!$response instanceof HetrixtoolsServiceResponse){

            $output->write(self::NO_RESPONSE_FROM_HETRIX);
            return false;
        }

        if($response->status == HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS){

            if($response->blacklisted_count > 0){

                try{

                    $messagesSent = $this->azineMailgunService->sendBlacklistNotification($response);

                    if($messagesSent > 0){

                        $output->write(self::BLACKLIST_REPORT_WAS_SENT);
                    }
                }
                catch (\Exception $e){

                    $output->write($e->getMessage(), true);
                }
            }
            else{

                $output->write(self::IP_IS_NOT_BLACKLISTED);
            }
        }
    }
}