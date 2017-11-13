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
use Doctrine\ORM\EntityManager;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService;
use Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService;

/**
 * Checks if the last ip address from MailgunEvent entity is in blacklist
 *
 */
class CheckIpAddressIsBlacklistedCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mailgun:check-ip-in-blacklist')
            ->setDescription('Checks if the last ip address from MailgunEvent entity is in blacklist');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventRepository = $this->getEntityManager()->getRepository('AzineMailgunWebhooksBundle:MailgunEvent');
        $translator = $this->getContainer()->get('translator');
        $ipAddress = $eventRepository->getLastEvent()->getIp();

        $response = $this->getHetrixtoolsService()->checkIpAddressInBlacklist($ipAddress);

        if(!$response instanceof HetrixtoolsServiceResponse){

            $output->write($translator->trans("No response from Hetrixtools service, try later."), true);
            return false;
        }

        if($response->status == HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS){

            if($response->blacklisted_count > 0){

                try{

                    $messagesSent = $this->getAzineMailgunServiceService()->sendBlacklistNotification($response);

                    if($messagesSent > 0){

                        $output->write($translator->trans("Blacklist report was sent."), true);
                    }
                }
                catch (\Exception $e){

                    $output->write($e->getMessage(), true);
                }
            }
        }
    }

    /**
     * Get EntityManager
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Get AzineMailgunHetrixtoolsService
     * @return AzineMailgunHetrixtoolsService
     */
    private function getHetrixtoolsService()
    {
        return $this->getContainer()->get('azine.mailgun.hetrixtools.service');
    }

    /**
     * Get AzineMailgunMailerService
     * @return AzineMailgunMailerService
     */
    private function getAzineMailgunServiceService()
    {
        return $this->getContainer()->get('azine.mailgun.mailer');
    }
}