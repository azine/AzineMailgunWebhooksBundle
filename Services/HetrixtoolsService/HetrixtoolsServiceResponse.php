<?php


namespace Azine\MailgunWebhooksBundle\Services\HetrixtoolsService;


class HetrixtoolsServiceResponse
{
    const RESPONSE_STATUS_SUCCESS = 'SUCCESS';
    const RESPONSE_STATUS_ERROR = 'ERROR';

    /**
     * @var string
     */
    public $status;

    /**
     * @var int
     */
    public $api_calls_left;

    /**
     * @var int
     */
    public $blacklist_check_credits_left;

    /**
     * @var int
     */
    public $blacklisted_count;

    /**
     * @var array
     */
    public $blacklisted_on;

    /**
     * @var array
     */
    public $links;

    /**
     * @var string
     */
    public $error_message;

    /**
     * @param string $response
     * @throws \InvalidArgumentException
     * @return HetrixtoolsServiceResponse $responseObject
     */
    public static function fromJson($response)
    {
        $response = json_decode($response, true);

        if($response instanceof \stdClass) {
            throw new \InvalidArgumentException('Invalid JSON provided');
        }

        $responseObject = new self();

        foreach($responseObject as $key => $value){

            if(isset($response[$key])){

                $responseObject->$key = $response[$key];
            }
        }

        return $responseObject;
    }
}