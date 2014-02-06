<?php
namespace Azine\MailgunWebhooksBundle\Services;

use Doctrine\ORM\EntityManager;

use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @author Dominik Businger
 */
class AzineMailgunService {

	/**
	 * @var ManagerRegistry
	 */
	private $managerRegistry;

	public function __construct(ManagerRegistry $managerRegistry) {
		$this->managerRegistry = $managerRegistry;
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

		$qb = $this->getManager()->createQueryBuilder()
			->delete("Azine\MailgunWebhooksBundle\Entity\MailgunEvent", "e")
			->andWhere("e.timestamp < :age")
			->setParameter("age", $ageLimit->getTimestamp());

		if($type != null){
			$qb->andWhere("e.event = :type")
				->setParameter("type", $type);
		}

		return $qb->getQuery()->execute();
	}

	/**
	 * @return EntityManager
	 */
	private function getManager(){
		return $this->managerRegistry->getManager();
	}
}
