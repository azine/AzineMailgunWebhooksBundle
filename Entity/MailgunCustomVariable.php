<?php

namespace Azine\MailgunWebhooksBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailgunCustomVariable
 */
class MailgunCustomVariable
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $eventId;

    /**
     * @var integer
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set eventId
     *
     * @param integer $eventId
     * @return MailgunCustomVariable
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
    
        return $this;
    }

    /**
     * Get eventId
     *
     * @return integer 
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * Set variableName
     *
     * @param integer $variableName
     * @return MailgunCustomVariable
     */
    public function setVariableName($variableName)
    {
        $this->variableName = $variableName;
    
        return $this;
    }

    /**
     * Get variableName
     *
     * @return integer 
     */
    public function getVariableName()
    {
        return $this->variableName;
    }

    /**
     * Set content
     *
     * @param array $content
     * @return MailgunCustomVariable
     */
    public function setContent($content)
    {
        $this->content = $content;
    
        return $this;
    }

    /**
     * Get content
     *
     * @return array 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set event
     *
     * @param \Azine\MailgunWebhooksBundle\Entity\MailgunEvent $event
     * @return MailgunCustomVariable
     */
    public function setEvent(\Azine\MailgunWebhooksBundle\Entity\MailgunEvent $event = null)
    {
        $this->event = $event;
    
        return $this;
    }

    /**
     * Get event
     *
     * @return \Azine\MailgunWebhooksBundle\Entity\MailgunEvent 
     */
    public function getEvent()
    {
        return $this->event;
    }
}
