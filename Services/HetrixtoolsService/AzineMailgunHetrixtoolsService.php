<?php

namespace Azine\MailgunWebhooksBundle\Services\HetrixtoolsService;

/**
 * AzineMailgunHetrixtoolsService
 *
 * This service is a wrapper for using Hetrixtools blacklist check functionality https://hetrixtools.com/.
 */
class AzineMailgunHetrixtoolsService
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $blacklistIpCheckUrl;

    /**
     * AzineMailgunHetrixtoolsService constructor.
     *
     * @param string $apiKey
     * @param string $url
     */
    public function __construct($apiKey, $url)
    {
        $this->apiKey = $apiKey;
        $this->blacklistIpCheckUrl = $url;
    }

    /**
     * @param string $ip
     * @return HetrixtoolsServiceResponse $response
     */
    public function checkIpAddressInBlacklist($ip)
    {
        $url = $this->prepareBlacklistIpCheckUrl($ip);
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        $info = curl_getinfo($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $response = substr($server_output, $header_size);

        if($info['http_code'] != 200){

            return null;
        }

        $response = HetrixtoolsServiceResponse::fromJson($response);

        return $response;
    }

    /**
     * @param string $ip
     * @throws \InvalidArgumentException
     * @return string $url
     */
    private function prepareBlacklistIpCheckUrl($ip)
    {
        if ($ip == null || !filter_var($ip, FILTER_VALIDATE_IP)) {

            throw new \InvalidArgumentException('Given Ip address is invalid');
        }

        if($this->apiKey == null){

            throw new \InvalidArgumentException('Api key for Hetrixtools blacklist check service is not set');
        }

        $url = str_replace('<API_TOKEN>', $this->apiKey, $this->blacklistIpCheckUrl);
        $url = str_replace('<IP_ADDRESS>', $ip, $url);

        return $url;
    }
}