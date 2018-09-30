<?php

namespace Azine\MailgunWebhooksBundle\Tests\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailgunControllerTest extends WebTestCase
{
    public function testShowOverviewAction()
    {
        $this->checkApplication();

        // Create a new client to browse the application
        $client = static::createClient();

        $manager = $this->getEntityManager();
        $eventReop = $manager->getRepository("Azine\MailgunWebhooksBundle\Entity\MailgunEvent");
        $events = sizeof($eventReop->findAll());
        $bounced = sizeof($eventReop->findBy(array('event' => 'bounced')));
        $dropped = sizeof($eventReop->findBy(array('event' => 'dropped')));
        $spam = sizeof($eventReop->findBy(array('event' => 'complained')));
        $unsubscribed = sizeof($eventReop->findBy(array('event' => 'unsubscribed')));

        // view the list of events
        $listUrl = substr($this->getRouter()->generate('mailgun_overview', array('_locale' => 'en')), 13);
        $crawler = $this->loginUserIfRequired($client, $listUrl);
        $pageSize = 10;
        $this->assertSame($pageSize + 1, $crawler->filter('.eventsTable tr')->count(), "$pageSize Mailgun events (+1 header row) expected on this page ($listUrl)!");
        $this->assertSame(1, $crawler->filter("li #eventCount:contains('EventList ($events)')")->count(), "'EventList ($events)' expected on page.");
        $this->assertSame(1, $crawler->filter("li li a:contains('bounced ($bounced)')")->count(), "'bounced ($bounced)' expected on page.");
        $this->assertSame(1, $crawler->filter("li li a:contains('dropped ($dropped)')")->count(), "'dropped ($dropped)' expected on page.");
        $this->assertSame(1, $crawler->filter("li li a:contains('marked as spam by the user ($spam)')")->count(), "'marked as spam by the user ($spam)' expected on page.");
        $this->assertSame(1, $crawler->filter("li li a:contains('unsubscribe requests by users ($unsubscribed)')")->count(), "'unsubscribe requests by users ($unsubscribed)' expected on page.");
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
