<?php

namespace Azine\MailgunWebhooksBundle\Entity;

/**
 * MailgunEvent
 */
class MailgunEvent
{
    const CREATE_EVENT = "azine.mailgun.webhooks.event.create";
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARN = 'warning';
    const SEVERITY_ERROR= 'error';

    public function getEventTitle()
    {
        $title = "";
        $headers = $this->getMessageHeaders();
        if (array_key_exists("Subject", $headers)) {
            $title = $headers["Subject"];
        }

        return $title;
    }

    /**
     * Set messageHeaders
     *
     * In the loop, the values can also be an array.
     *
     * @param  string                                           $messageHeaders
     * @return \Azine\MailgunWebhooksBundle\Entity\MailgunEvent
     */
    public function setMessageHeaders($messageHeaders)
    {
        $headers = json_decode($messageHeaders, true);
        $this->messageHeaders = array();
        foreach ($headers as $key => $value) {
            $this->messageHeaders[$key] = $value;
        }

        return $this;
    }

    public function getDateTime()
    {
        return new \DateTime("@".$this->getTimestamp());
    }

    /**
     * Get messageHeaders
     *
     * @return array
     */
    public function getMessageHeaders()
    {
        if (!is_array($this->messageHeaders)) {
            $this->setMessageHeaders($this->messageHeaders);
        }

        return $this->messageHeaders;
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
     * @var string
     */
    private $event;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $notification;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var string
     */
    private $recipient;

    /**
     * @var string
     */
    private $errorCode;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var string
     */
    private $error;

    /**
     * @var string
     */
    private $country;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $region;

    /**
     * @var string
     */
    private $campaignId;

    /**
     * @var string
     */
    private $campaignName;

    /**
     * @var string
     */
    private $clientName;

    /**
     * @var string
     */
    private $clientOs;

    /**
     * @var string
     */
    private $clientType;

    /**
     * @var string
     */
    private $deviceType;

    /**
     * @var string
     */
    private $mailingList;

    /**
     * @var array
     */
    private $messageHeaders;

    /**
     * @var string
     */
    private $messageId;

    /**
     * @var string
     */
    private $tag;

    /**
     * @var string
     */
    private $userAgent;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $token;

    /**
     * @var integer
     */
    private $timestamp;

    /**
     * @var string
     */
    private $signature;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $variables;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $attachments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->variables = new \Doctrine\Common\Collections\ArrayCollection();
        $this->attachments = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set event
     *
     * @param  string       $event
     * @return MailgunEvent
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set domain
     *
     * @param  string       $domain
     * @return MailgunEvent
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set description
     *
     * @param  string       $description
     * @return MailgunEvent
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set notification
     *
     * @param  string       $notification
     * @return MailgunEvent
     */
    public function setNotification($notification)
    {
        $this->notification = $notification;

        return $this;
    }

    /**
     * Get notification
     *
     * @return string
     */
    public function getNotification()
    {
        return $this->notification;
    }

    /**
     * Set reason
     *
     * @param  string       $reason
     * @return MailgunEvent
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set recipient
     *
     * @param  string       $recipient
     * @return MailgunEvent
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Get recipient
     *
     * @return string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Set errorCode
     *
     * @param  string       $errorCode
     * @return MailgunEvent
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * Get errorCode
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Set ip
     *
     * @param  string       $ip
     * @return MailgunEvent
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set error
     *
     * @param  string       $error
     * @return MailgunEvent
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Get error
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set country, if not Unknown
     *
     * @param  string       $country
     * @return MailgunEvent
     */
    public function setCountry($country)
    {
        if ($country !== 'Unknown') {
            $this->country = $country;
        }

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set city
     *
     * @param  string       $city
     * @return MailgunEvent
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set region, if not Unknown
     *
     * @param  string       $region
     * @return MailgunEvent
     */
    public function setRegion($region)
    {
        if ($region !== 'Unknown') {
            $this->region = $region;
        }

        return $this;
    }

    /**
     * Get region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set campaignId
     *
     * @param  string       $campaignId
     * @return MailgunEvent
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    /**
     * Get campaignId
     *
     * @return string
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * Set campaignName
     *
     * @param  string       $campaignName
     * @return MailgunEvent
     */
    public function setCampaignName($campaignName)
    {
        $this->campaignName = $campaignName;

        return $this;
    }

    /**
     * Get campaignName
     *
     * @return string
     */
    public function getCampaignName()
    {
        return $this->campaignName;
    }

    /**
     * Set clientName
     *
     * @param  string       $clientName
     * @return MailgunEvent
     */
    public function setClientName($clientName)
    {
        $this->clientName = $clientName;

        return $this;
    }

    /**
     * Get clientName
     *
     * @return string
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    /**
     * Set clientOs
     *
     * @param  string       $clientOs
     * @return MailgunEvent
     */
    public function setClientOs($clientOs)
    {
        $this->clientOs = $clientOs;

        return $this;
    }

    /**
     * Get clientOs
     *
     * @return string
     */
    public function getClientOs()
    {
        return $this->clientOs;
    }

    /**
     * Set clientType
     *
     * @param  string       $clientType
     * @return MailgunEvent
     */
    public function setClientType($clientType)
    {
        $this->clientType = $clientType;

        return $this;
    }

    /**
     * Get clientType
     *
     * @return string
     */
    public function getClientType()
    {
        return $this->clientType;
    }

    /**
     * Set deviceType
     *
     * @param  string       $deviceType
     * @return MailgunEvent
     */
    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    /**
     * Get deviceType
     *
     * @return string
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }

    /**
     * Set mailingList
     *
     * @param  string       $mailingList
     * @return MailgunEvent
     */
    public function setMailingList($mailingList)
    {
        $this->mailingList = $mailingList;

        return $this;
    }

    /**
     * Get mailingList
     *
     * @return string
     */
    public function getMailingList()
    {
        return $this->mailingList;
    }

    /**
     * Set messageId
     *
     * @param  string       $messageId
     * @return MailgunEvent
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Get messageId
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set tag
     *
     * @param  string       $tag
     * @return MailgunEvent
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set userAgent
     *
     * @param  string       $userAgent
     * @return MailgunEvent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Get userAgent
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Set url
     *
     * @param  string       $url
     * @return MailgunEvent
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set token
     *
     * @param  string       $token
     * @return MailgunEvent
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set timestamp
     *
     * @param  integer      $timestamp
     * @return MailgunEvent
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return integer
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set signature
     *
     * @param  string       $signature
     * @return MailgunEvent
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Add variables
     *
     * @param  \Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable $variables
     * @return MailgunEvent
     */
    public function addVariable(\Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable $variables)
    {
        $this->variables[] = $variables;

        return $this;
    }

    /**
     * Remove variables
     *
     * @param \Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable $variables
     */
    public function removeVariable(\Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable $variables)
    {
        $this->variables->removeElement($variables);
    }

    /**
     * Get variables
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Add attachments
     *
     * @param  \Azine\MailgunWebhooksBundle\Entity\MailgunAttachment $attachments
     * @return MailgunEvent
     */
    public function addAttachment(\Azine\MailgunWebhooksBundle\Entity\MailgunAttachment $attachments)
    {
        $this->attachments[] = $attachments;

        return $this;
    }

    /**
     * Remove attachments
     *
     * @param \Azine\MailgunWebhooksBundle\Entity\MailgunAttachment $attachments
     */
    public function removeAttachment(\Azine\MailgunWebhooksBundle\Entity\MailgunAttachment $attachments)
    {
        $this->attachments->removeElement($attachments);
    }

    /**
     * Get attachments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttachments()
    {
        return $this->attachments;
    }
}
