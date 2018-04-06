<?php

namespace Azine\MailgunWebhooksBundle\Entity;

/**
 * MailgunCustomVariable.
 */
class MailgunCustomVariable
{
    /**
     * @param MailgunEvent $event
     */
    public function __construct(MailgunEvent $event)
    {
        $this->setEvent($event);
    }

    ///////////////////////////////////////////////////////////////////
    // generated stuff only below this line.
    // @codeCoverageIgnoreStart
    ///////////////////////////////////////////////////////////////////

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $eventId;

    /**
     * @var int
     */
    private $variableName;

    /**
     * @var array
     */
    private $content;

    /**
     * @var \Azine\MailgunWebhooksBundle\Entity\MailgunEvent
     */
    private $event;

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
     * Set eventId.
     *
     * @param int $eventId
     *
     * @return MailgunCustomVariable
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;

        return $this;
    }

    /**
     * Get eventId.
     *
     * @return int
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * Set variableName.
     *
     * @param int $variableName
     *
     * @return MailgunCustomVariable
     */
    public function setVariableName($variableName)
    {
        $this->variableName = $variableName;

        return $this;
    }

    /**
     * Get variableName.
     *
     * @return int
     */
    public function getVariableName()
    {
        return $this->variableName;
    }

    /**
     * Set content.
     *
     * @param array $content
     *
     * @return MailgunCustomVariable
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set event.
     *
     * @param \Azine\MailgunWebhooksBundle\Entity\MailgunEvent $event
     *
     * @return MailgunCustomVariable
     */
    public function setEvent(\Azine\MailgunWebhooksBundle\Entity\MailgunEvent $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event.
     *
     * @return \Azine\MailgunWebhooksBundle\Entity\MailgunEvent
     */
    public function getEvent()
    {
        return $this->event;
    }
}
