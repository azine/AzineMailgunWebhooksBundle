<?php

namespace Azine\MailgunWebhooksBundle\Tests;

use Azine\MailgunWebhooksBundle\Entity\MailgunAttachment;
use Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TestHelper
{
    /**
     * @param int $count
     */
    public static function addMailgunEvents(EntityManager $manager, $count, $mailgunApiKey)
    {
        $eventTypes = array('delivered', 'bounced', 'opened', 'dropped');
        while ($count > 0) {
            $e = new MailgunEvent();
            $e->setEvent($eventTypes[rand(0, sizeof($eventTypes) - 1)]);
            $e->setDomain('acme');
            $d = new \DateTime(rand(1, 200).' days '.rand(1, 86400).' seconds ago');
            $e->setTimestamp($d->getTimestamp());
            $e->setToken(md5(time().$count));
            $e->setRecipient('recipient-'.$count.'@email.com');
            $e->setSender('some-sender-'.$count.'@email.com');
            $e->setMessageHeaders(json_encode(array('some_json' => 'data', 'Subject' => "this mail was sent because it's important.")));
            $e->setMessageId('<'.md5(time()).$count.'@acme.com>');
            $e->setSignature(hash_hmac('SHA256', $e->getTimestamp().$e->getToken(), $mailgunApiKey));
            $e->setDescription('some description');
            $e->setReason('don\'t know the reason');
            $e->setIp('42.42.42.42');
            $e->setErrorCode('123');
            $e->setCountry('CH');
            $e->setCity('Zurich');
            $e->setRegion('8000');
            $e->setCampaignId('2014-01-01');
            $e->setCampaignName('newsletter');
            $e->setClientName('some client');
            $e->setClientOs('some os');
            $e->setClientType('some type');
            $e->setDeviceType('some device');
            $e->setMailingList('no list');
            $e->setTag('hmmm no tag');
            $e->setUserAgent('Firefox 42');
            $e->setUrl('');
            $manager->persist($e);

            $file = new UploadedFile(realpath(__DIR__.'/testAttachment.small.png'), 'some.real.file.name1.png');
            $attachment = new MailgunAttachment($e);
            $attachment->setContent(file_get_contents($file->getRealPath()));
            $attachment->setName(md5(time() + rand(0, 100)).'.'.$file->getClientOriginalExtension());
            $attachment->setSize($file->getSize());
            $attachment->setType($file->getType());
            $attachment->setCounter(1);
            $manager->persist($attachment);

            $attachment = new MailgunAttachment($e);
            $attachment->setContent(file_get_contents($file->getRealPath()));
            $attachment->setName(md5(time() + rand(0, 100)).'.'.$file->getClientOriginalExtension());
            $attachment->setSize($file->getSize());
            $attachment->setType($file->getType());
            $attachment->setCounter(2);
            $manager->persist($attachment);

            $variable = new MailgunCustomVariable($e);
            $variable->setEventId($e->getId());
            $variable->setContent(array('some data1'));
            $variable->setVariableName('some custom variable for event'.$e->getId());
            $manager->persist($variable);

            $variable = new MailgunCustomVariable($e);
            $variable->setEventId($e->getId());
            $variable->setContent(array('some data2'));
            $variable->setVariableName('some custom variable for event'.$e->getId());
            $manager->persist($variable);

            --$count;
        }
        $manager->flush();
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
