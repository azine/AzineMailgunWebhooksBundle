<?php

namespace Azine\MailgunWebhooksBundle\Tests\Controller;

use Azine\MailgunWebhooksBundle\DependencyInjection\AzineMailgunWebhooksExtension;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Tests\TestHelper;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailgunEventControllerTest extends WebTestCase
{
    public function testWebHookCreateAndEventDispatching()
    {
        $this->checkApplication();

        $client = static::createClient();
        $client->request('GET', '/');

        // create a subscriber to listen to create_events.
        $subscriberMock = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Tests\EventSubscriberMock")->setMethods(array('handleCreate'))->getMock();
        $this->assertTrue($subscriberMock  instanceof EventSubscriberInterface);
        $this->getEventDispatcher()->addSubscriber($subscriberMock);
        $this->assertTrue($this->getEventDispatcher()->hasListeners(MailgunEvent::CREATE_EVENT));

        //		dominik I would expect the method handleCreate to be called once, but for some reason it is not.
        //		$subscriberMock->expects($this->once())->method("handleCreate");

        // get webhook url
        $url = $this->getRouter()->generate('mailgunevent_webhook', array('_locale', 'en'), UrlGeneratorInterface::ABSOLUTE_URL);

        $manager = $this->getEntityManager();
        $eventReop = $manager->getRepository("Azine\MailgunWebhooksBundle\Entity\MailgunEvent");
        $count = sizeof($eventReop->findAll());

        // post invalid data to the webhook-url and check the response & database
        $invalidPostData = $this->getInvalidPostData();
        $webhookdata = json_encode($invalidPostData);
        $attachments = array(
            'attachment-1' => new UploadedFile(realpath(__DIR__.'/../testAttachment.small.png'), 'some.real.file.name1.png'),
            'attachment-2' => new UploadedFile(realpath(__DIR__.'/../testAttachment.small.png'), 'some.real.file.name2.png'),
            'attachment-3' => new UploadedFile(realpath(__DIR__.'/../testAttachment.small.png'), 'some.real.file.name3.png'),
        );
        $crawler = $client->request('POST', $url, $invalidPostData, $attachments);

        $this->assertSame(401, $client->getResponse()->getStatusCode(), "Response-Code 401 expected for post-data with invalid signature: \n\n$webhookdata\n\n\n");
        $this->assertContains('Signature verification failed.', $crawler->text(), 'Response expected.');
        $this->assertSame($count, sizeof($eventReop->findAll()), 'No new db entry for the webhook expected!');

        // post valid data to the webhook-url and check the response
        $validPostData = $this->getValidPostData();
        $webhookdata = json_encode($validPostData);
        $crawler = $client->request('POST', $url, $validPostData, $attachments);
        $this->assertSame(200, $client->getResponse()->getStatusCode(), "Response-Code 200 expected for '$url'.\n\n$webhookdata\n\n\n".$client->getResponse()->getContent());
        $this->assertContains('Thanx, for the info.', $crawler->text(), 'Response expected.');
        $this->assertSame($count + 1, sizeof($eventReop->findAll()), 'One new db entry for the webhook expected!');
    }

    private function getValidPostData()
    {
        $postData = TestHelper::getPostDataWithoutSignature();
        $postData['signature'] = $this->getValidSignature($postData['token'], $postData['timestamp']);

        return $postData;
    }

    private function getInvalidPostData()
    {
        $postData = TestHelper::getPostDataWithoutSignature();
        $postData['signature'] = 'invalid-signature';

        return $postData;
    }

    /**
     * @param string $token
     * @param int    $timestamp
     */
    private function getValidSignature($token, $timestamp)
    {
        $key = $this->getContainer()->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::API_KEY);
        $signature = hash_hmac('SHA256', $timestamp.$token, $key);

        return $signature;
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
        $listUrl = substr($this->getRouter()->generate('mailgunevent_list', array('_locale' => 'en', 'page' => 1, 'pageSize' => $pageSize, 'clear' => true)), 13);
        $crawler = $this->loginUserIfRequired($client, $listUrl);
        $this->assertSame($pageSize + 1, $crawler->filter('.eventsTable tr')->count(), "$pageSize Mailgun events (+1 header row) expected on this page ($listUrl)!");

        // view a single event
        $link = $crawler->filter('.eventsTable tr a:first-child')->first()->link();
        $posLastSlash = strrpos($link->getUri(), '/');
        $posOfIdStart = strrpos($link->getUri(), '/', -6) + 1;
        $eventId = substr($link->getUri(), $posOfIdStart, $posLastSlash - $posOfIdStart);
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode(), 'Status 200 expected.');
        $this->assertSame($eventId, $crawler->filter('td')->first()->text(), "Content of first td should be the eventId ($eventId)");

        // delete the event from show-page
        $link = $crawler->selectLink('delete')->link();
        $crawler = $client->click($link);

        // check that it is gone from the list
        $this->assertSame(0, $crawler->filter("#event$eventId")->count(), 'The deleted event should not be in the list anymore.');

        // delete the event from list-page
        $crawler = $client->followRedirect();
        $link = $crawler->filter('.eventsTable tr .deleteLink')->first()->link();
        $delUri = $link->getUri();
        $eventId = substr($delUri, strrpos($delUri, '/') + 1);
        $crawler = $client->click($link);

        // check that it is gone from the list
        $this->assertSame(0, $crawler->filter("#event$eventId")->count(), 'The deleted event should not be in the list anymore.');

        // filter the list for something
        $crawler = $client->followRedirect();
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
        $url = substr($this->getRouter()->generate('mailgunevent_delete', array('_locale' => 'en', 'eventId' => $inexistentEventId)), 13);
        $client->request('GET', $url);
        $this->assertSame(404, $client->getResponse()->getStatusCode(), "404 expected for invalid eventId ($inexistentEventId).");

        $url = substr($this->getRouter()->generate('mailgunevent_show', array('_locale' => 'en', 'id' => $inexistentEventId)), 13);
        $client->request('GET', $url);
        $this->assertSame(404, $client->getResponse()->getStatusCode(), '404 expected.');

        // show inexistent page
        $maxPage = floor($count / $pageSize);
        $beyondListUrl = $this->getRouter()->generate('mailgunevent_list', array('_locale' => 'en', 'page' => $maxPage + 1, 'pageSize' => $pageSize, 'clear' => true));
        $client->request('GET', $beyondListUrl);
        $crawler = $client->followRedirect();
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

        // make sure there is some data in the application
        if (sizeof($eventReop->findAll()) < 5) {
            TestHelper::addMailgunEvents($manager, 5, $apiKey);
        }

        $events = $eventReop->findAll();

        $testTokenValue = 'testValue';
        $messageHeader = array(AzineMailgunWebhooksExtension::WEB_VIEW_TOKEN => $testTokenValue);
        $events[0]->setMessageHeaders(json_encode($messageHeader));
        $manager->persist($events[0]);
        $manager->flush();

        $events = $eventReop->findAll();
        $pageSize = count($events);

        $listUrl = substr($this->getRouter()->generate('mailgunevent_list', array('_locale' => 'en', 'page' => 1, 'pageSize' => $pageSize, 'clear' => true)), 13);
        $crawler = $this->loginUserIfRequired($client, $listUrl);

        $this->assertSame(1, $crawler->filter("ul:contains('".AzineMailgunWebhooksExtension::WEB_VIEW_TOKEN."')")->count(), 'There should be events with the webView headers in the list');
        $this->assertSame(1, $crawler->filter("ul a:contains('".$testTokenValue."')")->count(), 'There should be events with the webView links in the list');
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
        } catch (\RuntimeException $ex) {
            $this->markTestSkipped('There does not seem to be a full application available (e.g. running tests on travis.org). So this test is skipped.');

            return;
        }
    }
}
