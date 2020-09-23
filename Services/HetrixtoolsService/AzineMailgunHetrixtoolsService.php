<?php

namespace Azine\MailgunWebhooksBundle\Services\HetrixtoolsService;

use Psr\Log\LoggerInterface;

/**
 * AzineMailgunHetrixtoolsService.
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
     * @var LoggerInterface 
     */
    private $logger;
    
    /**
     * AzineMailgunHetrixtoolsService constructor.
     *
     * @param LoggerInterface $logger
     * @param string $apiKey
     * @param string $url
     */
    public function __construct(LoggerInterface $logger, $apiKey, $url)
    {
        $this->apiKey = $apiKey;
        $this->blacklistIpCheckUrl = $url;
        $this->logger = $logger;
    }

    /**
     * @param string $ip
     *
     * @return HetrixtoolsServiceResponse $response
     */
    public function checkIpAddressInBlacklist($ip)
    {
        $url = $this->prepareBlacklistIpCheckUrl($ip);

        $jsonResponse = $this->executeCheck($url);
        $hetrixtoolsServiceResponse = HetrixtoolsServiceResponse::fromJson($jsonResponse);

        return $hetrixtoolsServiceResponse;
    }

    /**
     * sends a get request to the given url.
     *
     * @param string $url
     *
     * @return string $response| null
     */
    protected function executeCheck($url)
    {
        $this->logger->debug("Calling $url to check HetrixTools for SPAM-List entries.");
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        $info = curl_getinfo($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        
        $this->logger->debug("Hetrix returned: ". print_r($info, true));

        if (200 != $info['http_code']) {
            return null;
        }

        $response = substr($server_output, $header_size);

        return $response;
    }

    /**
     * @param string $ip
     *
     * @throws \InvalidArgumentException
     *
     * @return string $url
     */
    private function prepareBlacklistIpCheckUrl($ip)
    {
        if (null == $ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Given Ip address is invalid');
        }

        if (null == $this->apiKey) {
            throw new \InvalidArgumentException('Api key for Hetrixtools blacklist check service is not set');
        }

        $url = str_replace('<API_TOKEN>', $this->apiKey, $this->blacklistIpCheckUrl);
        $url = str_replace('<IP_ADDRESS>', $ip, $url);

        return $url;
    }
}
