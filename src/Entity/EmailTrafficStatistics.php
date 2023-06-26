<?php

namespace Azine\MailgunWebhooksBundle\Entity;

/**
 * EmailTrafficStatistics.
 */
class EmailTrafficStatistics
{
    const SPAM_ALERT_SENT = 'spam_alert_sent';
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $action;

    /**
     * @var \DateTime
     */
    private $created;

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
     * Set action.
     *
     * @param string $action
     *
     * @return EmailTrafficStatistics
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get $action.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return EmailTrafficStatistics
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }
}
