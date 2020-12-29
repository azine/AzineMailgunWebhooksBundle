<?php

namespace Azine\MailgunWebhooksBundle\Entity;

/**
 * HetrixToolsBlacklistResponseNotification.
 */
class HetrixToolsBlacklistResponseNotification
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var array
     */
    private $data;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var \DateTime
     */
    private $ignoreUntil;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set data.
     *
     * @param array $data
     *
     * @return HetrixToolsBlacklistResponseNotification
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return HetrixToolsBlacklistResponseNotification
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set ip.
     *
     * @param string $ip
     *
     * @return HetrixToolsBlacklistResponseNotification
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip.
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set ignoreUntil.
     *
     * @param \DateTime $ignoreUntil
     *
     * @return HetrixToolsBlacklistResponseNotification
     */
    public function setIgnoreUntil($ignoreUntil)
    {
        $this->ignoreUntil = $ignoreUntil;

        return $this;
    }

    /**
     * Get ignoreUntil.
     *
     * @return \DateTime
     */
    public function getIgnoreUntil()
    {
        return $this->ignoreUntil;
    }
}
