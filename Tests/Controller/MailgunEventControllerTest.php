<?php

namespace Azine\MailgunWebhooksBundle\Tests\Controller;

use Azine\MailgunWebhooksBundle\DependencyInjection\AzineMailgunWebhooksExtension;
use Azine\MailgunWebhooksBundle\Entity\EmailTrafficStatistics;
use Azine\MailgunWebhooksBundle\Tests\TestHelper;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailgunWebhookControllerTest extends WebTestCase
{
    private $testStartTime;

    protected function setUp()
    {
        $this->testStartTime = new \DateTime();
    }

    protected function tearDown()
    {
        $manager = $this->getEntityManager();
        $queryBuilder = $manager->getRepository(EmailTrafficStatistics::class)->createQueryBuilder('e');
        $ets = $queryBuilder->where('e.created >= :testStartTime')
            ->setParameter('testStartTime', $this->testStartTime)
            ->getQuery()->execute();

        if (null != $ets && sizeof($ets) > 0) {
            foreach ($ets as $next) {
                $manager->remove($next);
            }
            $manager->flush();
        }
    }

    public function testWebHookCreateAndEventDispatchingOldAPI()
    {
        $this->checkApplication();

        $validPostData = $this->getValidPostData(false);
        $invalidPostData = $this->getInvalidPostData(false);
        $this->internalWebHookCreateAndEventDispatching($validPostData, $invalidPostData, false);
    }

    public function testWebHookCreateAndEventDispatchingNewAPI()
    {
        $this->checkApplication();

        $validPostData = $this->getValidPostData(true);
        $invalidPostData = $this->getInvalidPostData(true);
        $this->internalWebHookCreateAndEventDispatching($validPostData, $invalidPostData, true);
    }

    private function internalWebHookCreateAndEventDispatching($validPostData, $invalidPostData, $newApi)
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $client->enableProfiler();

        // get webhook url
        $url = $this->makeAbsolutPath($this->getRouter()->generate('mailgunevent_webhook', array('_locale', 'en'), UrlGeneratorInterface::ABSOLUTE_URL), 'en');

        $manager = $this->getEntityManager();
        $eventReop = $manager->getRepository("Azine\MailgunWebhooksBundle\Entity\MailgunEvent");
        $count = sizeof($eventReop->findAll());

        $attachments = array(
            'attachment-1' => new UploadedFile(realpath(__DIR__.'/../testAttachment.small.png'), 'some.real.file.name1.png'),
            'attachment-2' => new UploadedFile(realpath(__DIR__.'/../testAttachment.small.png'), 'some.real.file.name2.png'),
            'attachment-3' => new UploadedFile(realpath(__DIR__.'/../testAttachment.small.png'), 'some.real.file.name3.png'),
        );

        // post invalid data to the webhook-url and check the response & database
        $webhookdata = json_encode($invalidPostData);
        if ($newApi) {
            $crawler = $client->request('POST', $url, array(), $attachments, array(), $webhookdata);
        } else {
            $crawler = $client->request('POST', $url, $invalidPostData, $attachments);
        }

        $this->assertSame(401, $client->getResponse()->getStatusCode(), "Response-Code 401 expected for post-data with invalid signature: \n\n$webhookdata\n\n\n");
        $this->assertContains('Signature verification failed.', $crawler->text(), 'Response expected.');
        $this->assertSame($count, sizeof($eventReop->findAll()), 'No new db entry for the webhook expected!');

        // post valid data to the webhook-url and check the response
        $webhookdata = json_encode($validPostData);
        if ($newApi) {
            $crawler = $client->request('POST', $url, array(), $attachments, array(), $webhookdata);
        } else {
            $crawler = $client->request('POST', $url, $validPostData, $attachments);
        }
        $this->assertSame(200, $client->getResponse()->getStatusCode(), "Response-Code 200 expected for '$url'.\n\n$webhookdata\n\n\n".$client->getResponse()->getContent());
        $this->assertContains('Thanx, for the info.', $crawler->text(), 'Response expected.');
        $this->assertSame($count + 1, sizeof($eventReop->findAll()), 'One new db entry for the webhook expected!');

        // post valid data to the webhook-url and check the response
        if ($newApi) {
            $validPostData['event-data']['event'] = 'opened';
            $webhookdata = json_encode($validPostData);
            $crawler = $client->request('POST', $url, array(), $attachments, array(), $webhookdata);
            $crawler = $client->request('POST', $url, array(), $attachments, array(), $webhookdata);
        } else {
            $validPostData['event'] = 'opened';
            $webhookdata = json_encode($validPostData);
            $crawler = $client->request('POST', $url, $validPostData, $attachments);
            $crawler = $client->request('POST', $url, $validPostData, $attachments);
        }
        $this->assertSame(200, $client->getResponse()->getStatusCode(), "Response-Code 200 expected for '$url'.\n\n$webhookdata\n\n\n".$client->getResponse()->getContent());
        $this->assertContains('Thanx, for the info.', $crawler->text(), 'Response expected.');

        // post a complaint event to check if mail is triggered.
        if ($newApi) {
            $validPostData['event-data']['event'] = 'complained';
            $webhookdata = json_encode($validPostData);
            $crawler = $client->request('POST', $url, array(), $attachments, array(), $webhookdata);
        } else {
            $validPostData['event'] = 'complained';
            $webhookdata = json_encode($validPostData);
            $crawler = $client->request('POST', $url, $validPostData, $attachments);
        }
        $this->assertSame(200, $client->getResponse()->getStatusCode(), "Response-Code 200 expected for '$url'.\n\n$webhookdata\n\n\n".$client->getResponse()->getContent());
        $this->assertContains('Thanx, for the info.', $crawler->text(), 'Response expected.');
        $this->assertSame($count + 4, sizeof($eventReop->findAll()), 'One new db entry for the webhook expected!');

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        // checks that an email was sent from the listener
        $this->assertSame(1, $mailCollector->getMessageCount());
    }

    private function getValidPostData($newApi)
    {
        $postData = TestHelper::getPostDataWithoutSignature($newApi);

        $key = 'fake_api_key'; //$this->getContainer()->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::API_KEY);

        if ($newApi) {
            $postData['signature']['signature'] = hash_hmac('SHA256', $postData['signature']['timestamp'].$postData['signature']['token'], $key);
        } else {
            $postData['signature'] = hash_hmac('SHA256', $postData['timestamp'].$postData['token'], $key);
        }

        return $postData;
    }

    private function getInvalidPostData($newApi)
    {
        $postData = TestHelper::getPostDataWithoutSignature($newApi);

        if ($newApi) {
            $postData['signature']['signature'] = 'invalid-signature';
        } else {
            $postData['signature'] = 'invalid-signature';
        }

        return $postData;
    }

    public function testShowLog()
    {
        $this->checkApplication();

        // Create a new client to browse the application
        $client = static::createClient();
        $client->followRedirects();

        $manager = $this->getEntityManager();
        $eventReop = $manager->getRepository("Azine\MailgunWebhooksBundle\Entity\MailgunEvent");

        $apiKey = $this->getContainer()->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::API_KEY);

        // make sure there is plenty of data in the application to be able to verify paging
        if (sizeof($eventReop->findAll()) < 102) {
            TestHelper::addMailgunEvents($manager, 102, $apiKey);
        }
        $count = sizeof($eventReop->findAll());

        // view the list of events
        $pageSize = 25;
        $listUrl = $this->makeAbsolutPath($this->getRouter()->generate('mailgunevent_list', array('_locale' => 'en', 'page' => 1, 'pageSize' => $pageSize, 'clear' => true)), 'en');
        $crawler = $this->loginUserIfRequired($client, $listUrl);
        $this->assertSame($pageSize + 1, $crawler->filter('.eventsTable tr')->count(), "$pageSize Mailgun events (+1 header row) expected on this page ($listUrl)!");

        // view a single event
        $link = $crawler->filter('.eventsTable tr a:first-child')->first()->link();
        $posLastSlash = strrpos($link->getUri(), '/');
        $posOfIdStart = strrpos($link->getUri(), '/', -6) + 1;
        $eventId = substr($link->getUri(), $posOfIdStart, $posLastSlash - $posOfIdStart);
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode(), 'Status 200 expected.');
        $this->assertSame($eventId, $crawler->filter('.mailgunEvent td')->eq(1)->html(), "Content should be the eventId ($eventId)".$client->getResponse()->getContent());

        // delete the event from show-page
        $link = $crawler->selectLink('Delete')->link();
        $crawler = $client->click($link);
        $crawler = $client->followRedirect();
        // check that it is gone from the list
        $this->assertSame(0, $crawler->filter("#event$eventId")->count(), 'The deleted event should not be in the list anymore.');

        // filter the list for something
        $form = $crawler->selectButton('Filter')->form();
        $form['filter[eventType]']->select('delivered');
        $crawler = $client->submit($form);
        $this->assertSame($crawler->filter('.eventsTable tr')->count() - 1, $crawler->filter(".eventsTable a:contains('delivered')")->count(), "There should only be 'delivered' events in the list");

        // delete entry with xmlHttpRequest
        $eventToDelete = $eventReop->findOneBy(array());
        $ajaxUrl = $this->getRouter()->generate('mailgunevent_delete_ajax', array('_locale' => 'en'));
        $client->request('POST', $ajaxUrl, array('eventId' => $eventToDelete->getId()), array(), array('HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'));
        $this->assertSame('{"success":true}', $client->getResponse()->getContent(), "JSON response expcted from $ajaxUrl. for event with id:".$eventToDelete->getId());

        // show/delete inexistent log entry
        $inexistentEventId = md5('123invalid');
        $url = $this->makeAbsolutPath($this->getRouter()->generate('mailgunevent_delete', array('_locale' => 'en', 'eventId' => $inexistentEventId)), 'en');
        $client->request('GET', $url);
        $this->assertSame(404, $client->getResponse()->getStatusCode(), "404 expected for invalid eventId ($inexistentEventId).");

        $url = $this->makeAbsolutPath($this->getRouter()->generate('mailgunevent_show', array('_locale' => 'en', 'id' => $inexistentEventId)), 'en');
        $client->request('GET', $url);
        $this->assertSame(404, $client->getResponse()->getStatusCode(), '404 expected.');

        // show inexistent page
        $maxPage = floor($count / $pageSize);
        $beyondListUrl = $this->getRouter()->generate('mailgunevent_list', array('_locale' => 'en', 'page' => $maxPage + 1, 'pageSize' => $pageSize, 'clear' => true));
        $crawler = $client->followRedirects();
        $crawler = $client->request('GET', $beyondListUrl);
        $this->assertSame(2, $crawler->filter(".pagination .disabled:contains('Next')")->count(), 'Expected to be on the last page => the next button should be disabled.');
    }

    public function testWebViewLinks()
    {
        $this->checkApplication();

        // Create a new client to browse the application
        $client = static::createClient();
        $client->followRedirects();

        $manager = $this->getEntityManager();
        $eventReop = $manager->getRepository("Azine\MailgunWebhooksBundle\Entity\MailgunEvent");

        $apiKey = $this->getContainer()->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::API_KEY);

        // make sure there is data in the application
        $events = $eventReop->findAll();
        if (sizeof($events) < 5) {
            TestHelper::addMailgunEvents($manager, 5, $apiKey);
            $events = $eventReop->findAll();
        }

        $webViewTokenName = $this->getContainer()->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_TOKEN);

        $testTokenValue = 'testValue';
        $messageHeader = array($webViewTokenName => $testTokenValue);
        $events[0]->setMessageHeaders(json_encode($messageHeader));
        $manager->persist($events[0]);
        $manager->flush();

        $events = $eventReop->findAll();
        $pageSize = count($events);

        $listUrl = $this->makeAbsolutPath($this->getRouter()->generate('mailgunevent_list', array('_locale' => 'en', 'page' => 1, 'pageSize' => $pageSize, 'clear' => true)), 'en');
        $crawler = $this->loginUserIfRequired($client, $listUrl);

        $this->assertSame(1, $crawler->filter("ul:contains('$webViewTokenName')")->count(), 'There should be events with the webView headers in the list');
        $this->assertSame(1, $crawler->filter("ul a:contains('$testTokenValue')")->count(), 'There should be events with the webView links in the list');
    }

    /**
     * Load the url and login if required.
     *
     * @param string $url
     * @param string $username
     * @param Client $client
     *
     * @return Crawler $crawler of the page of the url or the page after the login
     */
    private function loginUserIfRequired(Client $client, $url, $username = 'dominik', $password = 'lkjlkjlkjlkj')
    {
        // try to get the url
        $client->followRedirects();
        $crawler = $client->request('GET', $url);

        $this->assertSame(200, $client->getResponse()->getStatusCode(), 'Status-Code 200 expected.');

        // if redirected to a login-page, login as admin-user
        if (5 == $crawler->filter('input')->count() && 1 == $crawler->filter('#username')->count() && 1 == $crawler->filter('#password')->count()) {
            // set the password of the admin
            $userProvider = $this->getContainer()->get('fos_user.user_provider.username_email');
            $user = $userProvider->loadUserByUsername($username);
            $user->setPlainPassword($password);
            $user->addRole('ROLE_ADMIN');

            $userManager = $this->getContainer()->get('fos_user.user_manager');
            $userManager->updateUser($user);

            $crawler = $crawler->filter("input[type='submit']");
            $form = $crawler->form();
            $form->get('_username')->setValue($username);
            $form->get('_password')->setValue($password);
            $crawler = $client->submit($form);
        }

        $this->assertSame(200, $client->getResponse()->getStatusCode(), 'Login failed.');
        $client->followRedirects(false);

        $this->assertStringEndsWith($url, $client->getRequest()->getUri(), "Login failed or not redirected to requested url: $url vs. ".$client->getRequest()->getUri());

        return $crawler;
    }

    /**
     * @var ContainerInterface
     */
    private $appContainer;

    /**
     * Get the current container.
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private function getContainer()
    {
        if (null == $this->appContainer) {
            $this->appContainer = static::$kernel->getContainer();
        }

        return $this->appContainer;
    }

    /**
     * @return UrlGeneratorInterface
     */
    private function getRouter()
    {
        return $this->getContainer()->get('router');
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return EventDispatcher
     */
    private function getEventDispatcher()
    {
        return $this->getContainer()->get('event_dispatcher');
    }

    /**
     * Check if the current setup is a full application.
     * If not, mark the test as skipped else continue.
     */
    private function checkApplication()
    {
        try {
            static::$kernel = static::createKernel(array());
            static::$kernel->boot();
        } catch (\RuntimeException $ex) {
            $this->markTestSkipped('There does not seem to be a full application available (e.g. running tests on travis.org). So this test is skipped.');

            return;
        }
    }

    private function makeAbsolutPath($url, $locale){
        $indexOfLocale = strpos($url, "/$locale/");
        if($indexOfLocale!==false){
            return substr($url, $indexOfLocale);
        }
        $indexOfLocalhost = strpos($url, "localhost");
        return substr($url, $indexOfLocalhost + 9);
    }
}
