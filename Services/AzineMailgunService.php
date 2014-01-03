<?php
namespace Azine\MailgunWebhooksBundle\Services;

use Doctrine\ORM\EntityManager;

/**
 * @author Dominik Businger
 */
class AzineMailgunService {

	/**
	 * @var EntityManager
	 */
	private $manager;

	public function __construct(EntityManager $entityManager){
		$this->manager = $entityManager;
	}

	public function removeOldEventEntries(\DateTime $ageLimit){
		return $this->removeEvents(null, $ageLimit);
	}

	public function removeEvents($type, \DateTime $ageLimit){

	}
}
