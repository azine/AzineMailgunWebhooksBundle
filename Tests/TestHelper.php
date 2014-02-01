<?php
namespace Azine\MailgunWebhooksBundle\Tests;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;

use Doctrine\ORM\EntityManager;


class TestHelper {

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

	    	$manager->persist($e);
	    	$count--;
    	}
    	$manager->flush();
    }

}
