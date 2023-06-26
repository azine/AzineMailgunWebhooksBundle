<?php

namespace Azine\MailgunWebhooksBundle\Controller;

use Azine\MailgunWebhooksBundle\DependencyInjection\AzineMailgunWebhooksExtension;
use Azine\MailgunWebhooksBundle\Entity\MailgunAttachment;
use Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\MailgunMessageSummary;
use Azine\MailgunWebhooksBundle\Entity\MailgunWebhookEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MailgunEvent controller.
 */
class MailgunWebhookController extends AbstractController
{
    public function createFromWebhookAction(Request $request)
    {
        // old webhooks api
        $params = $request->request->all();

        if (is_array($params) && !empty($params)) {
            $this->get('logger')->info('Creating MailgunEvent via old API.');

            return $this->createEventOldApi($params);
        }
        // new webhooks api
        $this->get('logger')->info('Creating MailgunEvent via new API.');
        $params = json_decode($request->getContent(), true);

        return $this->createEventNewApi($params);
    }

    private function createEventNewApi($paramsPre)
    {
        $params = array_change_key_case($paramsPre, CASE_LOWER);

        if (sizeof($params) != sizeof($paramsPre)) {
            $params['params_contained_duplicate_keys'] = $paramsPre;
        }

        /////////////////////////////////////////////////////
        // signature validation
        $signatureData = $params['signature'];
        $eventData = $params['event-data'];

        // check if the timestamp is fresh
        $timestamp = $signatureData['timestamp'];
        $tsAge = abs(time() - $timestamp);
        if ($tsAge > 15) {
            return new Response('Signature verification failed. Timestamp too old abs('.time()." - $timestamp) = $tsAge", 401);
        }

        // validate post-data
        $key = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::API_KEY);
        $token = $signatureData['token'];
        $expectedSignature = hash_hmac('SHA256', $timestamp.$token, $key);
        if ($expectedSignature != $signatureData['signature']) {
            return new Response('Signature verification failed.', 401);
        }

        /////////////////////////////////////////////////////
        // create event-entity
        try {
            // create event & populate with supplied data
            $event = new MailgunEvent();

            // token
            if (array_key_exists('token', $signatureData)) {
                $event->setToken($signatureData['token']);
                unset($signatureData['token']);
            }
            // timestamp
            if (array_key_exists('timestamp', $signatureData)) {
                $event->setTimestamp($signatureData['timestamp']);
                unset($signatureData['timestamp']);
            }
            // signature
            if (array_key_exists('signature', $signatureData)) {
                $event->setSignature($signatureData['signature']);
                unset($signatureData['signature']);
            }

            // event
            if (array_key_exists('event', $eventData)) {
                $event->setEvent($eventData['event']);
                unset($eventData['event']);
            }
            // domain
            if (array_key_exists('envelope', $eventData)) {
                $envelope = $eventData['envelope'];
                $sender = $envelope['sender'];
                $event->setSender($sender);
                $event->setDomain(substr($sender, strrpos($sender, '@') + 1));

                // ip
                if (array_key_exists('sending-ip', $envelope)) {
                    $event->setIp($envelope['sending-ip']);
                    unset($eventData['envelope']['sending-ip']);
                }

                unset($eventData['envelope']['sender']);
            }
            // description & reason
            if (array_key_exists('delivery-status', $eventData)) {
                $description = array_key_exists('message', $eventData['delivery-status']) ? $eventData['delivery-status']['message'].' ' : '';
                $description .= array_key_exists('description', $eventData['delivery-status']) ? $eventData['delivery-status']['description'] : '';

                $event->setDescription($description);
                unset($eventData['delivery-status']['message'], $eventData['delivery-status']['description']);

                // delivery status code
                if (array_key_exists('code', $eventData['delivery-status'])) {
                    $event->setErrorCode($eventData['delivery-status']['code']);
                    unset($eventData['delivery-status']['code']);
                }
            } elseif (array_key_exists('reject', $eventData)) {
                $description = array_key_exists('description', $eventData['reject']) ? $eventData['reject']['description'] : '';
                $reason = array_key_exists('reason', $eventData['reject']) ? $eventData['reject']['reason'] : '';
                $event->setDescription($description);
                $event->setReason($reason);
                unset($eventData['delivery-status']['description'], $eventData['delivery-status']['reason']);
            }
            // reason
            if (array_key_exists('reason', $eventData)) {
                $event->setReason($eventData['reason']);
                unset($eventData['reason']);
            }
            // recipient
            if (array_key_exists('recipient', $eventData)) {
                $event->setRecipient($eventData['recipient']);
                unset($eventData['recipient']);
            }
            if (array_key_exists('geolocation', $eventData)) {
                $geolocation = $eventData['geolocation'];
                // country
                if (array_key_exists('country', $geolocation)) {
                    $event->setCountry($geolocation['country']);
                    unset($eventData['geolocation']['country']);
                }
                // city
                if (array_key_exists('city', $geolocation)) {
                    $event->setCity($geolocation['city']);
                    unset($eventData['geolocation']['city']);
                }
                // region
                if (array_key_exists('region', $geolocation)) {
                    $event->setRegion($geolocation['region']);
                    unset($eventData['geolocation']['region']);
                }
            }
            if (array_key_exists('client-info', $eventData)) {
                $clientInfo = $eventData['client-info'];
                // clientName
                if (array_key_exists('client-name', $clientInfo)) {
                    $event->setClientName($clientInfo['client-name']);
                    unset($eventData['client-info']['client-name']);
                }
                // clientOs
                if (array_key_exists('client-os', $clientInfo)) {
                    $event->setClientOs($clientInfo['client-os']);
                    unset($eventData['client-info']['client-os']);
                }
                // clientType
                if (array_key_exists('client-type', $clientInfo)) {
                    $event->setClientType($clientInfo['client-type']);
                    unset($eventData['client-info']['client-type']);
                }
                // deviceType
                if (array_key_exists('device-type', $clientInfo)) {
                    $event->setDeviceType($clientInfo['device-type']);
                    unset($eventData['client-info']['device-type']);
                }
                // userAgent
                if (array_key_exists('user-agent', $clientInfo)) {
                    $event->setUserAgent($clientInfo['user-agent']);
                    unset($eventData['client-info']['user-agent']);
                }
            }

            if (array_key_exists('message', $eventData)) {
                $message = $eventData['message'];

                // messageHeaders
                if (array_key_exists('headers', $message)) {
                    $headers = $message['headers'];
                    // messageId
                    if (array_key_exists('message-id', $headers)) {
                        $trimmedMessageId = trim(trim($headers['message-id']), '<>');
                        $event->setMessageId($trimmedMessageId);

                        // set message domain from message id
                        if (null == $event->getDomain()) {
                            $event->setDomain(substr($trimmedMessageId, strrpos($trimmedMessageId, '@') + 1));
                        }
                    }

                    // sender
                    if (array_key_exists('from', $headers)) {
                        $event->setSender($headers['from']);
                    }

                    $event->setMessageHeaders(json_encode($headers));
                    unset($eventData['message']['headers']);
                }
            }
            // campaignName && campaignId
            if (array_key_exists('campaigns', $eventData)) {
                $event->setCampaignName(print_r($eventData['campaigns'], true));
                $event->setCampaignId(print_r($eventData['campaigns'], true));
                unset($eventData['campaigns']);
            }
            // tag
            if (array_key_exists('tags', $eventData)) {
                $event->setTag(print_r($eventData['tags'], true));
                unset($eventData['tags']);
            }
            // url
            if (array_key_exists('url', $eventData)) {
                $event->setUrl($eventData['url']);
                unset($eventData['url']);
            } elseif (array_key_exists('storage', $eventData)) {
                $event->setUrl($eventData['storage']['url']);
                unset($eventData['storage']['url']);
            }

            // mailingList
            if (array_key_exists('recipients', $eventData)) {
                $event->setmailingList(print_r($eventData['recipients'], true));
                unset($eventData['recipients']);
            }

            $manager = $this->container->get('doctrine.orm.entity_manager');
            $manager->persist($event);
            $eventSummary = $this->getDoctrine()->getRepository(MailgunMessageSummary::class)->createOrUpdateMessageSummary($event);

            $eventData = $this->removeEmptyArrayElements($eventData);

            // process the remaining posted values
            foreach ($eventData as $key => $value) {
                if (0 === strpos($key, 'attachment-')) {
                    // create event attachments
                    $attachment = new MailgunAttachment($event);
                    $attachment->setCounter(substr($key, 11));

                    // get the file
                    /* @var $value UploadedFile */
                    $attachment->setContent(file_get_contents($value->getPathname()));
                    $attachment->setSize($value->getSize());
                    $attachment->setType($value->getMimeType());
                    $attachment->setName($value->getFilename());

                    $manager->persist($attachment);
                } else {
                    // create custom-variables for event
                    $customVar = new MailgunCustomVariable($event);
                    $customVar->setVariableName($key);
                    $customVar->setContent($value);

                    $manager->persist($customVar);
                }
            }

            // save all entities
            $manager->flush();

            // Dispatch an event about the logging of a Webhook-call
            $this->get('event_dispatcher')->dispatch(MailgunEvent::CREATE_EVENT, new MailgunWebhookEvent($event));
        } catch (\Exception $e) {
            $this->container->get('logger')->warning('AzineMailgunWebhooksBundle: creating entities failed: '.$e->getMessage());
            $this->container->get('logger')->warning($e->getTraceAsString());

            return new Response(print_r($params, true).'AzineMailgunWebhooksBundle: creating entities failed: '.$e->getMessage(), 500);
        }

        // send response
        return new Response('Thanx, for the info.', 200);
    }

    private function createEventOldApi($paramsPre)
    {
        $params = array_change_key_case($paramsPre, CASE_LOWER);

        if (sizeof($params) != sizeof($paramsPre)) {
            $params['params_contained_duplicate_keys'] = $paramsPre;
        }

        // validate post-data
        $key = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::API_KEY);
        $timestamp = $params['timestamp'];

        // check if the timestamp is fresh
        $now = time();
        $tsAge = abs($now - $timestamp);
        if ($tsAge > 15) {
            return new Response("Signature verification failed. Timestamp too old abs($now - $timestamp)=$tsAge", 401);
        }

        $token = $params['token'];
        $expectedSignature = hash_hmac('SHA256', $timestamp.$token, $key);
        if ($expectedSignature != $params['signature']) {
            return new Response('Signature verification failed.', 401);
        }

        // drop unused variables
        if (array_key_exists('x-mailgun-sid', $params)) {
            unset($params['x-mailgun-sid']);
        }
        if (array_key_exists('attachment-count', $params)) {
            unset($params['attachment-count']);
        }

        try {
            // create event & populate with supplied data
            $event = new MailgunEvent();

            // event
            if (array_key_exists('event', $params)) {
                $event->setEvent($params['event']);
                unset($params['event']);
            }

            // domain
            if (array_key_exists('domain', $params)) {
                $event->setDomain($params['domain']);
                unset($params['domain']);
            }
            // description
            if (array_key_exists('description', $params)) {
                $event->setDescription($params['description']);
                unset($params['description']);
            }
            // reason
            if (array_key_exists('reason', $params)) {
                $event->setReason($params['reason']);
                unset($params['reason']);
            }
            // recipient
            if (array_key_exists('recipient', $params)) {
                $event->setRecipient($params['recipient']);
                unset($params['recipient']);
            }
            // errorCode
            if (array_key_exists('code', $params)) {
                $event->setErrorCode($params['code']);
                unset($params['code']);
            }
            // ip
            if (array_key_exists('ip', $params)) {
                $event->setIp($params['ip']);
                unset($params['ip']);
            }
            // error
            if (array_key_exists('error', $params)) {
                $event->setDescription($params['error']);
                unset($params['error']);
            }
            // country
            if (array_key_exists('country', $params)) {
                $event->setCountry($params['country']);
                unset($params['country']);
            }
            // city
            if (array_key_exists('city', $params)) {
                $event->setCity($params['city']);
                unset($params['city']);
            }
            // region
            if (array_key_exists('region', $params)) {
                $event->setRegion($params['region']);
                unset($params['region']);
            }
            // campaignId
            if (array_key_exists('campaign-id', $params)) {
                $event->setCampaignId($params['campaign-id']);
                unset($params['campaign-id']);
            }
            // campaignName	{
            if (array_key_exists('campaign-name', $params)) {
                $event->setCampaignName($params['campaign-name']);
                unset($params['campaign-name']);
            }
            // clientName
            if (array_key_exists('client-name', $params)) {
                $event->setClientName($params['client-name']);
                unset($params['client-name']);
            }
            // clientOs
            if (array_key_exists('client-os', $params)) {
                $event->setClientOs($params['client-os']);
                unset($params['client-os']);
            }
            // clientType
            if (array_key_exists('client-type', $params)) {
                $event->setClientType($params['client-type']);
                unset($params['client-type']);
            }
            // deviceType
            if (array_key_exists('device-type', $params)) {
                $event->setDeviceType($params['device-type']);
                unset($params['device-type']);
            }
            // mailingList
            if (array_key_exists('mailing-list', $params)) {
                $event->setmailingList($params['mailing-list']);
                unset($params['mailing-list']);
            }
            // messageHeaders
            if (array_key_exists('message-headers', $params)) {
                $headers = json_decode($params['message-headers']);
                foreach ($headers as $header) {
                    // sender
                    if ('Sender' == $header[0]) {
                        $event->setSender($header[1]);
                    }
                }
                $event->setMessageHeaders($params['message-headers']);
                unset($params['message-headers']);
            }
            // messageId
            if (array_key_exists('message-id', $params)) {
                $trimmedMessageId = trim(trim($params['message-id']), '<>');
                $event->setMessageId($trimmedMessageId);
                unset($params['message-id']);
            }
            // tag
            if (array_key_exists('tag', $params)) {
                $event->setTag($params['tag']);
                unset($params['tag']);
            }
            // x-mailgun-tag
            if (array_key_exists('x-mailgun-tag', $params)) {
                $event->setTag($params['x-mailgun-tag']);
                unset($params['x-mailgun-tag']);
            }
            // userAgent
            if (array_key_exists('user-agent', $params)) {
                $event->setUserAgent($params['user-agent']);
                unset($params['user-agent']);
            }
            // url
            if (array_key_exists('url', $params)) {
                $event->setUrl($params['url']);
                unset($params['url']);
            }
            // token
            if (array_key_exists('token', $params)) {
                $event->setToken($params['token']);
                unset($params['token']);
            }
            // timestamp
            if (array_key_exists('timestamp', $params)) {
                $event->setTimestamp($params['timestamp']);
                unset($params['timestamp']);
            }
            // signature
            if (array_key_exists('signature', $params)) {
                $event->setSignature($params['signature']);
                unset($params['signature']);
            }

            $manager = $this->container->get('doctrine.orm.entity_manager');
            $manager->persist($event);

            $this->getDoctrine()->getRepository(MailgunMessageSummary::class)->createOrUpdateMessageSummary($event);

            $params = $this->removeEmptyArrayElements($params);

            // process the remaining posted values
            foreach ($params as $key => $value) {
                if (0 === strpos($key, 'attachment-')) {
                    // create event attachments
                    $attachment = new MailgunAttachment($event);
                    $attachment->setCounter(substr($key, 11));

                    // get the file
                    /* @var UploadedFile $value */
                    $attachment->setContent(file_get_contents($value->getPathname()));
                    $attachment->setSize($value->getSize());
                    $attachment->setType($value->getMimeType());
                    $attachment->setName($value->getFilename());
                    $manager->persist($attachment);
                } else {
                    // create custom-variables for event
                    $customVar = new MailgunCustomVariable($event);
                    $customVar->setVariableName($key);
                    $customVar->setContent($value);
                    $manager->persist($customVar);
                }
            }

            // save all entities
            $manager->flush();

            // Dispatch an event about the logging of a Webhook-call
            $this->get('event_dispatcher')->dispatch(MailgunEvent::CREATE_EVENT, new MailgunWebhookEvent($event));
        } catch (\Exception $e) {
            $this->container->get('logger')->warning('AzineMailgunWebhooksBundle: creating entities failed: '.$e->getMessage());
            $this->container->get('logger')->warning($e->getTraceAsString());

            return new Response('AzineMailgunWebhooksBundle: creating entities failed: '.$e->getMessage(), 500);
        }

        // send response
        return new Response('Thanx, for the info.', 200);
    }

    /**
     * @param $haystack
     *
     * @return array without empty elements (recursively)
     */
    private function removeEmptyArrayElements($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = $this->removeEmptyArrayElements($haystack[$key]);
            }

            if (empty($haystack[$key])) {
                unset($haystack[$key]);
            }
        }

        return $haystack;
    }
}
