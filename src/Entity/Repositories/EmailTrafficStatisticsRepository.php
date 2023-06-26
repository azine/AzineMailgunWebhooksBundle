<?php

namespace Azine\MailgunWebhooksBundle\Entity\Repositories;

use Azine\MailgunWebhooksBundle\Entity\EmailTrafficStatistics;
use Doctrine\ORM\EntityRepository;

/**
 * EmailTrafficStatisticsRepository.
 *
 * This entity is used to store some events that occurred regarding mailgun.
 * E.g. when has the last notification about a SPAM complaint been sent to the administrator.
 */
class EmailTrafficStatisticsRepository extends EntityRepository
{
    /**
     * Get last EmailTrafficStatistics by action.
     *
     * @param $action
     *
     * @return EmailTrafficStatistics
     */
    public function getLastByAction($action)
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->setMaxResults(1)
            ->select('e')
            ->from($this->getEntityName(), 'e')
            ->where('e.action = :action')
            ->orderBy('e.created ', 'desc')
            ->setParameters(array('action' => $action));

        try {
            return $q->getQuery()->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}
