<?php
namespace Azine\MailgunWebhooksBundle\Tests\Services\HetrixtoolsService;


use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;

class HetrixtoolsServiceResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testHetrixtoolsServiceResponse()
    {
        $responseJson = '{
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

        $response = HetrixtoolsServiceResponse::fromJson($responseJson);

        $this->assertInstanceOf(HetrixtoolsServiceResponse::class, $response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHetrixtoolsServiceResponseEmptyJson()
    {
        //Test with empty string
        $responseJson = '';
        HetrixtoolsServiceResponse::fromJson($responseJson);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHetrixtoolsServiceResponseWrongJson()
    {
        //Test with invalid Json
        $responseJson = 'invalidJson';
        HetrixtoolsServiceResponse::fromJson($responseJson);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHetrixtoolsServiceResponseNullJson()
    {
        //Test with invalid Json
        HetrixtoolsServiceResponse::fromJson(null);
    }
}