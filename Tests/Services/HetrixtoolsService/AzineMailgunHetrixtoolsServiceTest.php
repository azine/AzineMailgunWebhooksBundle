<?php

namespace Azine\MailgunWebhooksBundle\Tests\Services\HetrixtoolsService;

use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;
use Azine\MailgunWebhooksBundle\Tests\AzineTestCase;

class AzineMailgunHetrixtoolsServiceTest extends AzineTestCase
{
    private $responseJson = '{
            "status": "SUCCESS",
            "api_calls_left": 1976,
            "blacklist_check_credits_left": 81,
            "blacklisted_count": 3,
            "blacklisted_on": [
                {
                    "rbl": "example1.com",
                    "delist": "https://1.example.com/ip/198.51.100.42"
                },
                {
                    "rbl": "example2.org",
                    "delist": "https://2.example.com/query/ip/198.51.100.42"
                },
                {
                    "rbl": "example3.org",
                    "delist": "https://3.example.com/query/ip/198.51.100.42"
                }
            ],
            "links": {
                "report_link": "https://3.example.com/report/blacklist/token/",
                "whitelabel_report_link": "",
                "api_report_link": "https://api.example.com/v1/token/report/198.51.100.42/",
                "api_blacklist_check_link": "https://api.example.com/v2/token/blacklist-check/ipv4/198.51.100.42/"
            }
        }';

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIfApiKeyisNotSet()
    {
        $apiKey = '';
        $url = 'blacklistIpCheckUr';

        $ip = '198.51.100.42';
        $service = new AzineMailgunHetrixtoolsService($apiKey, $url);
        $service->checkIpAddressInBlacklist($ip);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIfEmptyIpIsGiven()
    {
        $apiKey = 'testApiKey';
        $url = 'blacklistIpCheckUr';
        $ip = '';

        $service = new AzineMailgunHetrixtoolsService($apiKey, $url);
        $service->checkIpAddressInBlacklist($ip);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIfInvalidIpIsGiven()
    {
        $apiKey = 'testApiKey';
        $url = 'blacklistIpCheckUr';
        $ip = 'invalidIpAddress';

        $service = new AzineMailgunHetrixtoolsService($apiKey, $url);
        $service->checkIpAddressInBlacklist($ip);
    }

    public function testSuccessReponse()
    {
        $apiKey = 'test';
        $url = 'https://api.example.com/v2/testkey/blacklist-check/ipv4/198.51.100.42/';

        $hetrixtoolsService = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService")
            ->setConstructorArgs(array($apiKey, $url))
            ->setMethods(array('executeCheck'))->getMock();
        $hetrixtoolsService->expects($this->once())->method('executeCheck')->will($this->returnValue($this->responseJson));

        /** @var AzineMailgunHetrixtoolsService $hetrixtoolsService */
        $response = $hetrixtoolsService->checkIpAddressInBlacklist('198.51.100.42');

        $this->assertInstanceOf(HetrixtoolsServiceResponse::class, $response);
    }
}
