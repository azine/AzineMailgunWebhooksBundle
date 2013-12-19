<?php

namespace Azine\MailgunWebhooksBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailgunEvent
 */
class MailgunEvent{

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
     * @var array
     */
    private $customVariables;

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
     * @param string $event
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
     * @param string $domain
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
     * @param string $description
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
     * @param string $notification
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
     * @param string $reason
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
     * @param string $recipient
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
     * @param string $errorCode
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
     * @param string $ip
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
     * @param string $error
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
     * Set country
     *
     * @param string $country
     * @return MailgunEvent
     */
    public function setCountry($country)
    {
        $this->country = $country;

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
     * @param string $city
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
     * Set region
     *
     * @param string $region
     * @return MailgunEvent
     */
    public function setRegion($region)
    {
        $this->region = $region;

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
     * @param string $campaignId
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
     * @param string $campaignName
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
     * @param string $clientName
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
     * @param string $clientOs
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
     * @param string $clientType
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
     * @param string $deviceType
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
     * @param string $mailingList
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
     * Set messageHeaders
     *
     * @param array $messageHeaders
     * @return MailgunEvent
     */
    public function setMessageHeaders($messageHeaders)
    {
        $this->messageHeaders = $messageHeaders;

        return $this;
    }

    /**
     * Get messageHeaders
     *
     * @return array
     */
    public function getMessageHeaders()
    {
        return $this->messageHeaders;
    }

    /**
     * Set messageId
     *
     * @param string $messageId
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
     * @param string $tag
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
     * Set customVariables
     *
     * @param array $customVariables
     * @return MailgunEvent
     */
    public function setCustomVariables($customVariables)
    {
        $this->customVariables = $customVariables;

        return $this;
    }

    /**
     * Get customVariables
     *
     * @return array
     */
    public function getCustomVariables()
    {
        return $this->customVariables;
    }

    /**
     * Set userAgent
     *
     * @param string $userAgent
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
     * @param string $url
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
     * @param string $token
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
     * @param integer $timestamp
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
     * @param string $signature
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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $custom_variables;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $attachments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->custom_variables = new \Doctrine\Common\Collections\ArrayCollection();
        $this->attachments = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add custom_variables
     *
     * @param \Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable $customVariables
     * @return MailgunEvent
     */
    public function addCustomVariable(\Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable $customVariables)
    {
        $this->custom_variables[] = $customVariables;

        return $this;
    }

    /**
     * Remove custom_variables
     *
     * @param \Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable $customVariables
     */
    public function removeCustomVariable(\Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable $customVariables)
    {
        $this->custom_variables->removeElement($customVariables);
    }

    /**
     * Add attachments
     *
     * @param \Azine\MailgunWebhooksBundle\Entity\MailgunAttachment $attachments
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