<?php
namespace Azine\MailgunWebhooksBundle\Tests;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable;
use Azine\MailgunWebhooksBundle\Entity\MailgunAttachment;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Doctrine\ORM\EntityManager;


class TestHelper {

    /**
     * @param integer $count
     */
    public static function addMailgunEvents(EntityManager $manager, $count, $mailgunApiKey){
    	$eventTypes = array('delivered', 'bounced', 'opened', 'dropped');
    	while ($count > 0){
			$e = new MailgunEvent();
			$e->setEvent($eventTypes[rand(0, sizeof($eventTypes)-1)]);
			$e->setDomain('acme');
			$d = new \DateTime(rand(1, 200)." days ".rand(1, 86400)." seconds ago");
			$e->setTimestamp($d->getTimestamp());
			$e->setToken(md5(time().$count));
			$e->setRecipient('someone'.$count.'@email.com');
			$e->setMessageHeaders(json_encode(array('some_json' => 'data', 'Subject' => "this mail was sent because it's important.")));
			$e->setMessageId("<".md5(time()).$count."@acme.com>");
			$e->setSignature(hash_hmac("SHA256", $e->getTimestamp().$e->getToken(), $mailgunApiKey));
			$e->setDescription('some description');
			$e->setNotification('some notification');
			$e->setReason('don\'t know the reason');
			$e->setIp('42.42.42.42');
			$e->setError('some error');
			$e->setErrorCode("123");
			$e->setCountry('CH');
			$e->setCity('Zurich');
			$e->setRegion('8000');
			$e->setCampaignId('2014-01-01');
			$e->setCampaignName('newsletter');
			$e->setClientName('some client');
			$e->setClientOs('some os');
			$e->setClientType('some type');
			$e->setDeviceType('some device');
			$e->setMailingList('no list');
			$e->setTag('hmmm no tag');
			$e->setUserAgent('Firefox 42');
			$e->setUrl('');
			$manager->persist($e);

 			$file =  new UploadedFile(realpath(__DIR__."/testAttachment.small.png"), "some.real.file.name1.png");
			$attachment = new MailgunAttachment();
			$attachment->setEvent($e);
			$attachment->setContent(file_get_contents($file->getRealPath()));
			$attachment->setName(md5(time()+rand(0,100)).".".$file->getClientOriginalExtension());
			$attachment->setSize($file->getSize());
			$attachment->setType($file->getType());
			$attachment->setCounter(1);
 			$manager->persist($attachment);

// 			$attachment = new MailgunAttachment();
// 			$attachment->setEvent($e);
// 			$attachment->setContent(file_get_contents($file->getRealPath()));
// 			$attachment->setName(md5(time()+rand(0,100)).".".$file->getClientOriginalExtension());
// 			$attachment->setSize($file->getSize());
// 			$attachment->setType($file->getType());
// 			$attachment->setCounter(2);
// 			$manager->persist($attachment);

			$variable = new MailgunCustomVariable();
			$variable->setEvent($e);
			$variable->setEventId($e->getId());
			$variable->setContent(array('some data1'));
			$variable->setVariableName('some custom variable for event'.$e->getId());
			$manager->persist($variable);

			$variable = new MailgunCustomVariable();
			$variable->setEvent($e);
			$variable->setEventId($e->getId());
			$variable->setContent(array('some data2'));
			$variable->setVariableName('some custom variable for event'.$e->getId());
			$manager->persist($variable);

			$count--;
    	}
    	$manager->flush();
    }

    public static function getPostDataWithoutSignature(){
    	return array(
    			'event' => 'delivered',
    			'domain' => 'acme',
    			'timestamp' => time(),
    			'token' => 'c47468e81de0818af77f3e14a728602a29',
    			'X-Mailgun-Sid' => 'irrelevant',
    			'attachment-count' => 'irrelevant',
    			'recipient' => 'someone@email.com',
    			'message-headers' => json_encode(array('some_json' => 'data','Subject' => "this mail was sent because it's important.")),
    			'Message-Id' => "<02be51b250915313fa5fc58a497f8d37@acme.com>",
    			'description' => 'some description',
    			'notification' => 'some notification',
    			'reason' => 'don\'t know the reason',
    			'code' => 123,
    			'ip' => '42.42.42.42',
    			'error' => 'some error',
    			'country' => 'CH',
    			'city' => 'Zurich',
    			'region' => '8000',
    			'campaign-id' => '2014-01-01',
    			'campaign-name' => 'newsletter',
    			'client-name' => 'some client',
    			'client-os' => 'some os',
    			'client-type' => 'some type',
    			'device-type' => 'some device',
    			'mailing-list' => 'no list',
    			'tag' => 'hmmm no tag',
    			'user-agent' => 'Firefox 42',
    			'url' => '',
    			'duplicate-key' => "data1",
    			'Duplicate-key' => "data2",
    			'some-custom-var1' => 'some data1',
    			'some-custom-var2' => 'some data2',
    			'some-custom-var3' => 'some data3',
    			'attachment-1' => new UploadedFile(realpath(__DIR__."/testAttachment.small.png"), "some.real.file.name1.png"),
    			'attachment-2' => new UploadedFile(realpath(__DIR__."/testAttachment.small.png"), "some.real.file.name2.png"),
    			'attachment-3' => new UploadedFile(realpath(__DIR__."/testAttachment.small.png"), "some.real.file.name3.png"),

    	);
    }


}
