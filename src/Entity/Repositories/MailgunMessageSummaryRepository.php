<?php

namespace Azine\MailgunWebhooksBundle\Entity\Repositories;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\MailgunMessageSummary;

/**
 * MailgunMessageSummaryRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MailgunMessageSummaryRepository extends \Doctrine\ORM\EntityRepository
{
    public function getFieldsToOrderBy()
    {
        return array(
            'lastOpened',
            'sendDate',
            'firstOpened',
            'openCount',
            'fromAddress',
            'toAddress',
            'deliveryStatis',
            'subject',
            'senderIp',
        );
    }

    /**
     * Get distinct list of email recipients.
     *
     * @return array of string
     */
    public function getRecipients()
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->select('m.toAddress as address')
            ->from($this->getEntityName(), 'm')
            ->distinct()
            ->orderBy('m.toAddress ', 'asc')
            ->getQuery();

        $result = $this->processEmailLists($q->execute());

        return $result;
    }

    /**
     * Get distinct list of email senders.
     *
     * @return array of string
     */
    public function getSenders()
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->select('m.fromAddress as address')
            ->from($this->getEntityName(), 'm')
            ->distinct()
            ->orderBy('m.fromAddress ', 'asc')
            ->getQuery();

        $result = $this->processEmailLists($q->execute());

        return $result;
    }

    /**
     * @param $emailLists array of array['address']
     * @return array of unique email addresses
     */
    private function processEmailLists($emailLists){
        $result = array();
        foreach ($emailLists as $next) {
            foreach (mailparse_rfc822_parse_addresses($next['address']) as $recipient ){
                $email = strtolower($recipient['address']);
                if(array_search($email,$result) === false){
                    $result[] = $email;
                }
            }
        }
        sort($result);
        return $result;
    }

    public function getMessageSummaries($criteria, $orderBy, $limit, $offset)
    {
        $qb = $this->getMessageSummaryQuery($criteria);

        $orderField = key($orderBy);
        $orderDirection = $orderBy[$orderField];
        $qb->orderBy('m.'.$orderField, $orderDirection);
        if (-1 != $limit) {
            $qb->setMaxResults($limit);
            $qb->setFirstResult($offset);
        }

        $result = $qb->getQuery()->execute();

        return $result;
    }

    public function getMessageSummaryCount($criteria)
    {
        return $this->getMessageSummaryQuery($criteria)->select('count(m.id)')->getQuery()->getSingleScalarResult();
    }

    private function getMessageSummaryQuery($criteria)
    {
        $qb = $this->createQueryBuilder('m');
        if (array_key_exists('fromAddress', $criteria) && '' != $criteria['fromAddress']) {
            $qb->andWhere('m.fromAddress = :fromAddress')
                ->setParameter('fromAddress', $criteria['fromAddress']);
        }

        if (array_key_exists('toAddress', $criteria) && '' != $criteria['toAddress']) {
            $qb->andWhere('m.toAddress like :toAddress')
                ->setParameter('toAddress', '%'.$criteria['toAddress'].'%');
        }

        if (array_key_exists('search', $criteria) && '' != $criteria['search']) {
            $qb->andWhere('(m.toAddress like :search OR m.subject like :search OR m.fromAddress like :search OR m.deliveryStatus like :search)')
                ->setParameter('search', '%'.$criteria['search'].'%');
        }

        return $qb;
    }

    public function findSummary($fromAddress, $toAddresses, $sendTime, $subject)
    {
        // extract email-address part
        $from = mailparse_rfc822_parse_addresses($fromAddress)[0];

        $qb = $this->createQueryBuilder('m');
        $qb->where('m.sendDate < :higherBound AND m.sendDate > :lowerBound AND m.fromAddress like :fromAddress')
            ->setParameters('lowerBound', $lowerBound)
            ->setParameters('higherBound', $higherBound)
            ->setParameter('fromAddress', $from);

        // extract email-address parts
        $to = '';
        foreach (mailparse_rfc822_parse_addresses($toAddresses) as $nextMatch) {
            $to += "'".$nextMatch['address']."',";
        }
        $to = '('.trim($to, ', ').')';

        if (strlen($to) > 0) {
            $qb->andWhere('m.toAddress in :to')->setParameters('to', $to);
        }

        if (strlen($to) > 0) {
            $qb->andWhere('m.subject like :subject')->setParameters('subject', $subject);
        }
    }

    public function createOrUpdateMessageSummary(MailgunEvent $event)
    {
        $messageSummary = $this->findOneBy(array('id' => $event->getMessageId()));
        if (null == $messageSummary) {
            $ip = null != $event->getIp() ? $event->getIp() : 'unknown';
            $messageSummary = new MailgunMessageSummary($event->getMessageId(), $event->getDateTime(), $event->getSender(), $event->getRecipient(), 'no subject found yet', $ip);
        }
        $event->setEventSummary($messageSummary);
        $messageSummary->updateDeliveryStatus($event->getEvent());

        if ('opened' == $event->getEvent()) {
            if (null == $messageSummary->getFirstOpened() || $messageSummary->getFirstOpened() > $event->getDateTime()) {
                $messageSummary->setFirstOpened($event->getDateTime());
            }
            if (null == $messageSummary->getLastOpened() || $messageSummary->getLastOpened() < $event->getDateTime()) {
                $messageSummary->setLastOpened($event->getDateTime());
            }
            $messageSummary->increaseOpenCount();
        }

        if($messageSummary->getSenderIp() == 'unknown' && $event->getIp() != null){
            $messageSummary->setSenderIp($event->getIp());
        }

        foreach ($event->getMessageHeaders() as $key => $value) {
            if ('subject' == strtolower($key)) {
                $messageSummary->setSubject($value);
            } elseif ('sender' == strtolower($key)) {
                $messageSummary->setFromAddress($value);
            } elseif ('to' == strtolower($key)) {
                $messageSummary->appendToToAddress($value);
            } elseif ('cc' == strtolower($key)) {
                $messageSummary->appendToToAddress($value);
            } elseif ('bcc' == strtolower($key)) {
                $messageSummary->appendToToAddress($value);
            }
        }

        if($event->getRecipient() != null){
            $messageSummary->appendToToAddress($event->getRecipient());
        }

        $manager = $this->getEntityManager();
        $manager->persist($messageSummary);

        return $messageSummary;
    }
}