<?php

namespace Azine\MailgunWebhooksBundle\Services\HetrixtoolsService;

class HetrixtoolsServiceResponse
{
    const RESPONSE_STATUS_SUCCESS = 'SUCCESS';
    const RESPONSE_STATUS_ERROR = 'ERROR';
    const BLACKLIST_CHECK_IN_PROGRESS = 'blacklist check in progress for this ipv4';

    public $status;
    public $api_calls_left;
    public $blacklist_check_credits_left;
    public $blacklisted_count;
    public $blacklisted_on;
    public $links;
    public $error_message;

    /**
     * @throws \InvalidArgumentException
     */
    public static function fromJson(?string $response): self
    {
        if (null === $response || '' === $response) {
            throw new \InvalidArgumentException('Invalid JSON provided');
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON provided');
        }

        $responseObject = new self();
        foreach ($responseObject as $key => $value) {
            if (array_key_exists($key, $data)) {
                $responseObject->$key = $data[$key];
            }
        }

        return $responseObject;
    }
}
