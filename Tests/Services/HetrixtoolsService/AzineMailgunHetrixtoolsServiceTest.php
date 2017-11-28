<?php
namespace Azine\MailgunWebhooksBundle\Tests\Services\HetrixtoolsService;


use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;

class AzineMailgunHetrixtoolsServiceTest extends \PHPUnit_Framework_TestCase
{
    private $responseJson = '{
            "status": "SUCCESS",
            "api_calls_left": 1976,
            "blacklist_check_credits_left": 81,
            "blacklisted_count": 3,
            "blacklisted_on": [
                {
                    "rbl": "example1.com",
                    "delist": "https://example1.com/ip/44.44.44.44"
                },
                {
                    "rbl": "example2.org",
                    "delist": "https://www.example2.org/query/ip/44.44.44.44"
                },
                {
                    "rbl": "example3.org",
                    "delist": "https://www.example3.org/query/ip/44.44.44.44"
                }
            ],
            "links": {
                "report_link": "https://example3.com/report/blacklist/token/",
                "whitelabel_report_link": "",
                "api_report_link": "https://api.example3.com/v1/token/report/44.44.44.44/",
                "api_blacklist_check_link": "https://api.example3.com/v2/token/blacklist-check/ipv4/44.44.44.44/"
            }
        }';

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

    public function testSuccessReponse()
    {
        $apiKey = 'test';
        $url = 'https://api.example.com/v2/testkey/blacklist-check/ipv4/44.44.44.44/';

        $hetrixtoolsService = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService")
            ->setConstructorArgs([$apiKey, $url])
            ->setMethods(array('get'))->getMock();
        $hetrixtoolsService->expects($this->once())->method("get")->will($this->returnValue($this->responseJson));


        $response = $hetrixtoolsService->checkIpAddressInBlacklist('44.44.44.44');

        $this->assertInstanceOf(HetrixtoolsServiceResponse::class, $response);
    }
}