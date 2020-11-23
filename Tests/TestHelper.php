<?php

namespace Azine\MailgunWebhooksBundle\Tests;

use Azine\MailgunWebhooksBundle\Entity\MailgunAttachment;
use Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\MailgunMessageSummary;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunMessageSummaryRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TestHelper
{
    /**
     * @param int $count
     */
    public static function addMailgunEvents(EntityManager $manager, $count, $mailgunApiKey)
    {
        $eventTypes = array('delivered', 'bounced', 'dropped');
        while ($count > 0) {
            $eventType = $eventTypes[rand(0, sizeof($eventTypes) - 1)];
            $messageId = '<'.md5(time()).$count.'@acme.com>';
            $event = self::addMailgunEvent($manager, $mailgunApiKey, $eventType, $messageId, $count);
            $messageSummary = $manager->getRepository(MailgunMessageSummary::class)->createOrUpdateMessageSummary($event);

            // for delivered messages, add some open events
            if($eventType == 'delivered'){
                $openCount = random_int(0,10);
                while($openCount > 0) {
                    $openEvent = self::addMailgunEvent($manager, $mailgunApiKey, 'open', $messageId, $openCount);
                    $openEvent->setEventSummary($messageSummary);

                    $openCount--;
                }
            }
            $manager->flush();
            --$count;
        }
    }

    /**
     * @param EntityManager $manager
     * @param $mailgunApiKey
     * @param $eventType
     * @param $messageId
     * @throws \Exception
     */
    private static function addMailgunEvent(EntityManager $manager, $mailgunApiKey, $eventType, $messageId, $count){
        $mailgunEvent = new MailgunEvent();
        $mailgunEvent->setEvent($eventType);
        $mailgunEvent->setDomain('acme');
        $d = new \DateTime(rand(1, 200).' days '.rand(1, 86400).' seconds ago');
        $mailgunEvent->setTimestamp($d->getTimestamp());
        $mailgunEvent->setToken(md5(time().$count));
        $mailgunEvent->setRecipient('recipient-'.$count.'@email.com');
        $mailgunEvent->setSender('some-sender-'.$count.'@email.com');
        $mailgunEvent->setMessageHeaders(json_encode(array('some_json' => 'data', 'Subject' => "this mail was sent because it's important.")));
        $mailgunEvent->setMessageId($messageId);
        $mailgunEvent->setSignature(hash_hmac('SHA256', $mailgunEvent->getTimestamp().$mailgunEvent->getToken(), $mailgunApiKey));
        $mailgunEvent->setDescription('some description');
        $mailgunEvent->setReason('don\'t know the reason');
        $mailgunEvent->setIp('42.42.42.42');
        $mailgunEvent->setErrorCode('123');
        $mailgunEvent->setCountry('CH');
        $mailgunEvent->setCity('Zurich');
        $mailgunEvent->setRegion('8000');
        $mailgunEvent->setCampaignId('2014-01-01');
        $mailgunEvent->setCampaignName('newsletter');
        $mailgunEvent->setClientName('some client');
        $mailgunEvent->setClientOs('some os');
        $mailgunEvent->setClientType('some type');
        $mailgunEvent->setDeviceType('some device');
        $mailgunEvent->setMailingList('no list');
        $mailgunEvent->setTag('hmmm no tag');
        $mailgunEvent->setUserAgent('Firefox 42');
        $mailgunEvent->setUrl('');
        $manager->persist($mailgunEvent);

        $file = new UploadedFile(realpath(__DIR__.'/testAttachment.small.png'), 'some.real.file.name1.png');
        $attachment = new MailgunAttachment($mailgunEvent);
        $attachment->setContent(file_get_contents($file->getRealPath()));
        $attachment->setName(md5(time() + rand(0, 100)).'.'.$file->getClientOriginalExtension());
        $attachment->setSize($file->getSize());
        $attachment->setType($file->getType());
        $attachment->setCounter(1);
        $manager->persist($attachment);

        $attachment = new MailgunAttachment($mailgunEvent);
        $attachment->setContent(file_get_contents($file->getRealPath()));
        $attachment->setName(md5(time() + rand(0, 100)).'.'.$file->getClientOriginalExtension());
        $attachment->setSize($file->getSize());
        $attachment->setType($file->getType());
        $attachment->setCounter(2);
        $manager->persist($attachment);

        $variable = new MailgunCustomVariable($mailgunEvent);
        $variable->setEventId($mailgunEvent->getId());
        $variable->setContent(array('some data1'));
        $variable->setVariableName('some custom variable for event'.$mailgunEvent->getId());
        $manager->persist($variable);

        $variable = new MailgunCustomVariable($mailgunEvent);
        $variable->setEventId($mailgunEvent->getId());
        $variable->setContent(array('some data2'));
        $variable->setVariableName('some custom variable for event'.$mailgunEvent->getId());
        $manager->persist($variable);

        return $mailgunEvent;
    }

    public static function getPostDataWithoutSignature($newApi)
    {
        if($newApi){
            $timestamp = time();
            $data = array(
                'signature' => array (
                    'timestamp' => $timestamp,
                    'token' => '50dcec4a2d0ef27036c44ebd9ce9324736fc98ef9405428803',
                ),
                'event-data' =>
                    array(
                        'tags' =>
                            array(
                                0 => 'my_tag_1',
                                1 => 'my_tag_2'
                            ),
                        'timestamp' => $timestamp,
                        'storage' =>
                            array(
                                'url' => 'https://se.api.mailgun.net/v3/domains/acme.com/messages/message_key',
                                'key' => 'message_key',
                            ),
                        'envelope' =>
                            array(
                                'transport' => 'smtp',
                                'sender' => 'bob@acme.com',
                                'sending-ip' => '209.61.154.250',
                                'targets' => 'alice@example.com',
                            ),
                        'recipient-domain' => 'example.com',
                        'event' => 'delivered',
                        'campaigns' =>
                            array(),
                        'user-variables' =>
                            array(
                                'my_var_1' => 'Mailgun Variable #1',
                                'my-var-2' => 'awesome',
                            ),
                        'flags' =>
                            array('is-routed' => false, 'is-authenticated' => true, 'is-system-test' => false, 'is-test-mode' => false,
                            ),
                        'log-level' => 'info',
                        'message' =>
                            array(
                                'headers' =>
                                    array(
                                        'to' => 'Alice <alice@example.com>',
                                        'message-id' => '20130503182626.18666.16540@acme.com',
                                        'from' => 'Bob <bob@acme.com>',
                                        'subject' => 'Test delivered webhook',
                                    ),
                                'attachments' => array(),
                                'size' => 111,
                            ),
                        'recipient' => 'alice@example.com',
                        'id' => 'CPgfbmQMTCKtHW6uIWtuVe',
                        'delivery-status' =>
                            array(
                                'tls' => true,
                                'mx-host' => 'smtp-in.example.com',
                                'attempt-no' => 1,
                                'description' => '',
                                'session-seconds' => 0.4331989288330078,
                                'utf8' => true,
                                'code' => 250,
                                'message' => 'OK',
                                'certificate-verified' => true,
                            ),
                    ),
            );
        } else {
            $data = array(
                'event' => 'delivered',
                'domain' => 'acme',
                'timestamp' => time(),
                'token' => 'c47468e81de0818af77f3e14a728602a29',
                'X-Mailgun-Sid' => 'irrelevant',
                'attachment-count' => 'irrelevant',
                'recipient' => 'someone@email.com',
                'message-headers' => json_encode(array(
                    array('X-Mailgun-Sending-Ip', '198.62.234.37'),
                    array('X-Mailgun-Sid', 'WyIwN2U4YyIsICJzdXBwb3J0QGF6aW5lLm1lIiwgIjA2MjkzIl0='),
                    array('Received', 'from acme.test (b4.cme.test [194.140.238.63])'),
                    array('Sender', 'sender-name@acme.test'),
                    array('Message-Id', '<96eb9a44a61728bb77ac9073eb74cdc4@acme.test>'),
                    array('Date', 'Mon, 07 Sep 2020 14:38:41 +0200'),
                    array('Subject', 'Some email message subject'),
                    array('From', '\'acme.test sender-name\' <sender-name@acme.test>'),
                    array('To', '\'acme.test recipient-name\' <recipient-name@acme.test>'),
                    array('Mime-Version', '1.0'),
                    array('Content-Transfer-Encoding', '[\'quoted-printable\']')
                )),
                'Message-Id' => '<02be51b250915313fa5fc58a497f8d37@acme.com>',
                'description' => 'some description',
                'notification' => 'some notification',
                'reason' => 'don\'t know the reason',
                'code' => 123,
                'ip' => '42.42.42.42',
                'error' => 'some error',
                'country' => 'CH',
                'city' => 'Zurich',
                'region' => '8000',
                'campaign-id' => '2014-01-01',
                'campaign-name' => 'newsletter',
                'client-name' => 'some client',
                'client-os' => 'some os',
                'client-type' => 'some type',
                'device-type' => 'some device',
                'mailing-list' => 'no list',
                'tag' => 'hmmm no tag',
                'user-agent' => 'Firefox 42',
                'url' => '',
                'duplicate-key' => 'data1',
                'Duplicate-key' => 'data2',
                'some-custom-var1' => 'some data1',
                'some-custom-var2' => 'some data2',
                'some-custom-var3' => 'some data3',
            );
        }
        return $data;
    }
}
