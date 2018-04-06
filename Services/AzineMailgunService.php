<?php

namespace Azine\MailgunWebhooksBundle\Services;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

/**
 * @author Dominik Businger
 */
class AzineMailgunService
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Deletes all MailgunEvents that are older than the ageLimit.
     *
     * @param \DateTime $ageLimit
     *
     * @return int
     */
    public function removeOldEventEntries(\DateTime $ageLimit)
    {
        return $this->removeEvents(null, $ageLimit);
    }

    /**
     * Deletes all MailgunEvents of the given type(s) that are older than the ageLimit.
     *
     * Possible Types are:
     *
     * Event-Type		Description
     * ---------------------------------------------------------------------
     * accepted 		Mailgun accepted the request to send/forward the email and the message has been placed in queue.
     * rejected 		Mailgun rejected the request to send/forward the email.
     * delivered 		Mailgun sent the email and it was accepted by the recipient email server.
     * failed 			Mailgun could not deliver the email to the recipient email server.
     * opened 			The email recipient opened the email and enabled image viewing. Open tracking must be enabled in the Mailgun control panel, and the CNAME record must be pointing to mailgun.org.
     * clicked 		The email recipient clicked on a link in the email. Click tracking must be enabled in the Mailgun control panel, and the CNAME record must be pointing to mailgun.org.
     * unsubscribed 	The email recipient clicked on the unsubscribe link. Unsubscribe tracking must be enabled in the Mailgun control panel.
     * complained 		The email recipient clicked on the spam complaint button within their email client. Feedback loops enable the notification to be received by Mailgun.
     * stored 			Mailgun has stored an incoming message
     *
     * @param string|array of string|null $type
     * @param \DateTime                   $ageLimit
     *
     * @return int number of deleted records
     */
    public function removeEvents($type = null, \DateTime $ageLimit)
    {
        $qb = $this->getManager()->createQueryBuilder()->delete("Azine\MailgunWebhooksBundle\Entity\MailgunEvent", 'e')->andWhere('e.timestamp < :age')
                ->setParameter('age', $ageLimit->getTimestamp());

        if (is_string($type)) {
            $qb->andWhere('e.event = :type')->setParameter('type', $type);
        } elseif (is_array($type)) {
            $qb->andWhere('e.event in (:type)')->setParameter('type', $type);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Delete all MailgunEvents that match the given criteria (added as "andWhere" clauses).
     *
     * @param array $criteria
     *
     * @return \Doctrine\ORM\mixed
     */
    public function removeEventsBy(array $criteria)
    {
        $qb = $this->getManager()->createQueryBuilder()
            ->delete("Azine\MailgunWebhooksBundle\Entity\MailgunEvent", 'e');
        foreach ($criteria as $field => $value) {
            if (null == $value) {
                $qb->andWhere("e.$field is null");
            } else {
                $qb->andWhere("e.$field = :$field")
                    ->setParameter($field, $value);
            }
        }

        return $qb->getQuery()->execute();
    }

    /**
     * @return EntityManager
     */
    private function getManager()
    {
        return $this->managerRegistry->getManager();
    }
}
