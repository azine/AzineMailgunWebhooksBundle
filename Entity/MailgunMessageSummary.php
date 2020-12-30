<?php

namespace Azine\MailgunWebhooksBundle\Entity;

/**
 * MailgunMessageSummary.
 */
class MailgunMessageSummary
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $fromAddress;

    /**
     * @var string
     */
    private $toAddress;

    /**
     * @var \DateTime
     */
    private $firstOpened;

    /**
     * @var \DateTime
     */
    private $lastOpened;

    /**
     * @var int
     */
    private $openCount;

    /**
     * @var \DateTime
     */
    private $sendDate;

    /**
     * @var string
     */
    private $deliveryStatus;

    /**
     * @var string
     */
    private $senderIp;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $events;

    /**
     * Constructor.
     */
    public function __construct($id, \DateTime $sendDate, $fromAddress, $toAddress, $subject, $senderIp)
    {
        $this->events = new \Doctrine\Common\Collections\ArrayCollection();
        $this->id = $id;
        $this->sendDate = $sendDate;
        $this->fromAddress = $fromAddress;
        $this->toAddress = $toAddress;
        $this->subject = $subject;
        $this->senderIp = $senderIp;
        $this->openCount = 0;
    }

    /**
     * Append the latest event to the delivery status.
     *
     * @param $eventType
     */
    public function updateDeliveryStatus($eventType)
    {
        if (false === stripos($this->deliveryStatus, $eventType)) {
            $this->deliveryStatus = $this->deliveryStatus."$eventType, ";
        }
    }

    /**
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get fromAddress.
     *
     * @return string
     */
    public function getFromAddress()
    {
        return $this->fromAddress;
    }

    /**
     * Get toAddress.
     *
     * @return string
     */
    public function getToAddress()
    {
        return $this->toAddress;
    }

    /**
     * Set firstOpened.
     *
     * @param \DateTime $firstOpened
     *
     * @return MailgunMessageSummary
     */
    public function setFirstOpened($firstOpened)
    {
        $this->firstOpened = $firstOpened;

        return $this;
    }

    /**
     * Get firstOpened.
     *
     * @return \DateTime
     */
    public function getFirstOpened()
    {
        return $this->firstOpened;
    }

    /**
     * Set lastOpened.
     *
     * @param \DateTime $lastOpened
     *
     * @return MailgunMessageSummary
     */
    public function setLastOpened($lastOpened)
    {
        $this->lastOpened = $lastOpened;

        return $this;
    }

    /**
     * Get lastOpened.
     *
     * @return \DateTime
     */
    public function getLastOpened()
    {
        return $this->lastOpened;
    }

    /**
     * Increase openCount by 1.
     *
     * @return MailgunMessageSummary
     */
    public function increaseOpenCount()
    {
        ++$this->openCount;

        return $this;
    }

    /**
     * Get openCount.
     *
     * @return int
     */
    public function getOpenCount()
    {
        return $this->openCount;
    }

    /**
     * Get sendDate.
     *
     * @return \DateTime
     */
    public function getSendDate()
    {
        return $this->sendDate;
    }

    /**
     * Get deliveryStatus.
     *
     * @return string
     */
    public function getDeliveryStatus()
    {
        return trim($this->deliveryStatus, ', ');
    }

    /**
     * Get senderIp.
     *
     * @return string
     */
    public function getSenderIp()
    {
        return $this->senderIp;
    }

    /**
     * Set senderIp.
     *
     * @return MailgunMessageSummary
     */
    public function setSenderIp($ip)
    {
        $this->senderIp = $ip;

        return $this;
    }

    /**
     * Get events.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set subject.
     *
     * @return MailgunMessageSummary
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set fromAddress.
     *
     * @return MailgunMessageSummary
     */
    public function setFromAddress($fromAddress)
    {
        $this->fromAddress = $fromAddress;

        return $this;
    }

    /**
     * Set toAddress.
     *
     * @return MailgunMessageSummary
     */
    public function appendToToAddress($toAddress)
    {
        if (false === stripos($this->toAddress, $toAddress)) {
            $this->toAddress = trim($this->toAddress.', '.$toAddress, ', ');
        }

        return $this;
    }
}
