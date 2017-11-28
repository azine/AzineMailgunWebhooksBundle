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

        $response = HetrixtoolsServiceResponse::fromJson($responseJson);

        $this->assertInstanceOf(HetrixtoolsServiceResponse::class, $response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHetrixtoolsServiceResponseWrongJson()
    {
        //Test with empty string
        $responseJson = '';
        HetrixtoolsServiceResponse::fromJson($responseJson);

        //Test with invalid Json
        $responseJson = 'invalidJson';
        HetrixtoolsServiceResponse::fromJson($responseJson);
    }
}