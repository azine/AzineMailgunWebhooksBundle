<?php

namespace Azine\MailgunWebhooksBundle\Entity;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event for the symfony EventDispatcher-System
 */

class MailgunWebhookEvent extends Event {

	private $mailgunEvent;

	public function __construct(MailgunEvent $mailgunEvent){
		$this->mailgunEvent = $mailgunEvent;
	}

	public function getMailgunEvent(){
		return $this->mailgunEvent;
	}
}
