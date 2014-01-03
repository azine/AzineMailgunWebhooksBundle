<?php

namespace Azine\MailgunWebhooksBundle\Tests\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Azine\MailgunWebhooksBundle\DependencyInjection\AzineMailgunWebhooksExtension;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MailgunEventControllerTest extends WebTestCase {

	public function testWebHookCreateAndEventDispatching(){
		$this->checkApplication();

		$client = static::createClient();
		$client->request("GET", "/");

// 		$subscriberMock = $this->getMockBuilder("Symfony\Component\EventDispatcher\EventSubscriberInterface")->getMock();
// 		$subscriberMock->expects($this->once())->method("getSubscribedEvents")->will($this->returnValue(array(MailgunEvent::CREATE_EVENT => "onMailgunEventCreate")));
// 		$subscriberMock->expects($this->once())->method("onMailgunEventCreate");
// 		$this->getEventDispatcher()->addSubscriber($subscriberMock);

		// get webhook url
		$url = $this->getRouter()->generate("mailgunevent_webhook", array(), UrlGeneratorInterface::ABSOLUTE_URL);

		$manager = $this->getEntityManager();
		$eventReop = $manager->getRepository("Azine\MailgunWebhooksBundle\Entity\MailgunEvent");
		$count = sizeof($eventReop->findAll());

		// post invalid data to the webhook-url and check the response & database
		$webhookdata = json_encode($this->getInvalidPostData());
		$crawler = $client->request("POST", $url, $this->getInvalidPostData());

		$this->assertEquals(401, $client->getResponse()->getStatusCode(), "Response-Code 401 expected for invalid signature:");
		$this->assertContains("Signature verification failed.", $crawler->text(), "Response expected.");
		$this->assertEquals($count, sizeof($eventReop->findAll()), "No new db entry for the webhook expected!");

		// post valid data to the webhook-url and check the response
		$webhookdata = json_encode($this->getValidPostData());
		$crawler = $client->request("POST", $url, $this->getValidPostData());
		$this->assertEquals(200, $client->getResponse()->getStatusCode(), "Response-Code 200 expected for '$url'.\n\n$webhookdata");
		$this->assertContains("Thanx, for the info.", $crawler->text(), "Response expected.");
		$this->assertEquals($count + 1, sizeof($eventReop->findAll()), "One new db entry for the webhook expected!");

	}


	private function getValidPostData(){
		$postData = $this->getPostDataWithoutSignature();
		$postData['signature'] = $this->getValidSignature($postData['token'], $postData['timestamp']);
		return $postData;
	}

	private function getInvalidPostData(){
		$postData = $this->getPostDataWithoutSignature();
		$postData['signature'] = "invalid-signature";
		return $postData;
	}

	private function getPostDataWithoutSignature(){
		return array(
					'event' => 'delivered',
					'domain' => 'acme',
					'timestamp' => time(),
					'token' => 'c47468e81de0818af77f3e14a728602a29',
					'X-Mailgun-Sid' => 'irrelevant',
					'attachment-count' => 'irrelevant',
					'recipient' => 'someone@email.com',
					'message-headers' => json_encode(array('some_json' => 'data')),
					'Message-Id' => "<02be51b250915313fa5fc58a497f8d37@acme.com>",
// 					'description'
// 					'notification'
// 					'reason'
// 					'code'
// 					'ip'
// 					'error'
// 					'country'
// 					'city'
// 					'region'
// 					'campaign-id'
// 					'campaign-name'
// 					'client-name'
// 					'client-os'
// 					'device-type'
// 					'mailing-list'
// 					'tag'
// 					'user-agent'
// 					'url'
				);
	}

	public function testSignature(){
		$this->checkApplication();

		// boot the kernel
		static::createClient();

		$sig = $this->getValidSignature("some-token", 1387529061);
		$this->assertEquals('cc47468e81de0818af77f3e14a728602a2919b7fc09162e18f76ca12a9f8051d', $sig, "Valid signature expected.");
	}

	private function getValidSignature($token, $timestamp){
		$key = $this->getContainer()->getParameter(AzineMailgunWebhooksExtension::PREFIX."_".AzineMailgunWebhooksExtension::API_KEY);
		$signature = hash_hmac("SHA256", $timestamp.$token, $key);
		return $signature;
	}

	public function testShowLog()   {
    	$this->checkApplication();

        // Create a new client to browse the application
        $client = static::createClient();

        // make sure there is some data in the application

        // view the list of events
		$listUrl = $this->getRouter()->generate("mailgunevent_list", array('_locale' => "en", 'page' => 1, 'pageSize' => 25));

		// view a single event
		$showUrl = $this->getRouter()->generate("mailgunevent_show", array('_locale' => "en", 'id' => 22));

		// delete the event
		$deleteUrl = $this->getRouter()->generate("mailgunevent_delete", array('_locale' => "en", 'eventId' => 22));

		// check that it is gone from the list

		// filter the list for something


    }


    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Get the current container
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
	private function getContainer(){
		if($this->container == null){
			$this->container = static::$kernel->getContainer();
		}
		return $this->container;
	}

    /**
     * @return UrlGeneratorInterface
     */
	private function getRouter(){
		return $this->getContainer()->get('router');
	}

    /**
     * @return EntityManager
     */
	private function getEntityManager(){
		return $this->getContainer()->get('doctrine.orm.entity_manager');
	}

	/**
	 * @return EventDispatcher
	 */
	private function getEventDispatcher(){
		return $this->getContainer()->get("event_dispatcher");
	}


	/**
	 * Check if the current setup is a full application.
	 * If not, mark the test as skipped else continue.
	 */
	private function checkApplication(){
		try {
			static::$kernel = static::createKernel(array());
		} catch (\RuntimeException $ex){
			$this->markTestSkipped("There does not seem to be a full application available (e.g. running tests on travis.org). So this test is skipped.");
			return;
		}
	}
}
