<?php
namespace Azine\MailgunWebhooksBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Azine\MailgunWebhooksBundle\Entity\EmailTrafficStatistics;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Azine\MailgunWebhooksBundle\Tests\TestHelper;
use Azine\MailgunWebhooksBundle\DependencyInjection\AzineMailgunWebhooksExtension;
use Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService;

class AzineMailgunMailerServiceTest extends WebTestCase
{
    public function testSendSpamComplaintNotification()
    {
        $this->checkApplication();

        // Create a new client to browse the application
        $client = static::createClient();
        $client->request("GET", "/");
        $client->followRedirects();

        $mailer = $this->getContainer()->get('mailer');
        $twig = $this->getContainer()->get('twig');
        $translator = $this->getContainer()->get('translator');
        $fromEmail = 'sender@mail.com';
        $ticketId = '123';
        $ticketSubject = 'test';
        $ticketMessage = 'testMessage';
        $spamAlertsRecipientEmail = 'reciever@mail.com';
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $sendIntervalMinutes = 1;

        $mailgunMailerService = new AzineMailgunMailerService($mailer, $twig, $translator,$fromEmail, $ticketId,
            $ticketSubject, $ticketMessage, $spamAlertsRecipientEmail, $entityManager, $sendIntervalMinutes);

        $messageSent = $mailgunMailerService->sendSpamComplaintNotification(123);

        // Check that email was sent
        $this->assertEquals(1, $messageSent);

        $messageSent = $mailgunMailerService->sendSpamComplaintNotification(123);

        // Check that an email was not sent because the last email was sent less then azine_mailgun_webhooks_send_spam_alerts_interval
        $this->assertEquals(0, $messageSent);

        $timeToWait = ($sendIntervalMinutes + 1) * 60;
        sleep($timeToWait);
        $messageSent = $mailgunMailerService->sendSpamComplaintNotification(123);

        // Check that  email was sent after azine_mailgun_webhooks_send_spam_alerts_interval have passed
        $this->assertEquals(1, $messageSent);

    }


    /**
     * @var ContainerInterface
     */
    private $container;


    /**
     * Get the current container
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private function getContainer()
    {
        if ($this->container == null) {
            $this->container = static::$kernel->getContainer();
        }

        return $this->container;
    }

    /**
     * Check if the current setup is a full application.
     * If not, mark the test as skipped else continue.
     */
    private function checkApplication()
    {
        try {
            static::$kernel = static::createKernel(array());
        } catch (\RuntimeException $ex) {
            $this->markTestSkipped("There does not seem to be a full application available (e.g. running tests on travis.org). So this test is skipped.");

            return;
        }
    }
}