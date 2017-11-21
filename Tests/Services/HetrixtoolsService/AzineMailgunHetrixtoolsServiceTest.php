<?php
namespace Azine\MailgunWebhooksBundle\Tests\Services\HetrixtoolsService;


use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;

class AzineMailgunHetrixtoolsServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIfApiKeyisNotSet()
    {
        $apiKey = '';
        $url = 'blacklistIpCheckUr';

        $ip = '44.44.44.44';
        $service = new AzineMailgunHetrixtoolsService($apiKey, $url);
        $service->checkIpAddressInBlacklist($ip);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIfInvalidIpisGiven()
    {
        $apiKey = 'testApiKey';
        $url = 'blacklistIpCheckUr';

        //Test with empty ip
        $ip = '';
        $service = new AzineMailgunHetrixtoolsService($apiKey, $url);
        $service->checkIpAddressInBlacklist($ip);

        //Test with invalid ip

        $ip = 'invalidIpAddress';
        $service->checkIpAddressInBlacklist($ip);
    }
}