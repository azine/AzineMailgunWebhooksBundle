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

	/**
	 * Deletes all MailgunEvents that are older than the ageLimit
	 * @param \DateTime $ageLimit
	 */
	public function removeOldEventEntries(\DateTime $ageLimit){
		return $this->removeEvents(null, $ageLimit);
	}

	/**
	 * Deletes all MailgunEvents of the given type that are older than the ageLimit
	 * @param string $type
	 * @param \DateTime $ageLimit
	 * @return
	 */
	public function removeEvents($type = null, \DateTime $ageLimit){

		$qb = $this->manager->createQueryBuilder()
			->delete("Azine\MailgunWebhooksBundle\Entity\MailgunEvent", "e")
			->andWhere("e.timestamp < :age")
			->setParameter("age", $ageLimit->getTimestamp());

		if($type != null){
			$qb->andWhere("e.event = :type")
				->setParameter("type", $type);
		}

		return $qb->getQuery()->execute();
	}
}
