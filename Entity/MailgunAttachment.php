<?php

namespace Azine\MailgunWebhooksBundle\Entity;

/**
 * MailgunAttachment
 */
class MailgunAttachment{

	/**
	 * @param MailgunEvent $event
	 */
	public function __construct(MailgunEvent $event){
		$this->setEvent($event);
	}

	///////////////////////////////////////////////////////////////////
	// generated stuff only below this line.
	// @codeCoverageIgnoreStart
	///////////////////////////////////////////////////////////////////

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
    private $counter;

    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var integer
     */
    private $size;

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
     * @return MailgunAttachment
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
     * Set counter
     *
     * @param integer $counter
     * @return MailgunAttachment
     */
    public function setCounter($counter)
    {
        $this->counter = $counter;

        return $this;
    }

    /**
     * Get counter
     *
     * @return integer
     */
    public function getCounter()
    {
        return $this->counter;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return MailgunAttachment
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return MailgunAttachment
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return MailgunAttachment
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set size
     *
     * @param integer $size
     * @return MailgunAttachment
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set event
     *
     * @param \Azine\MailgunWebhooksBundle\Entity\MailgunEvent $event
     * @return MailgunAttachment
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
