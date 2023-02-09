<?php

namespace Klaviyo\Reclaim\Helper;

use Exception;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\ScopeSetting;

class Webhook extends \Magento\Framework\App\Helper\AbstractHelper
{
    const USER_AGENT = 'Klaviyo/MagentoTwo/Webhook';
    const WEBHOOK_URL = 'https://a.klaviyo.com/api/webhook/integration/magento_two';

    /**
     * Klaviyo logger helper
     * @var \Klaviyo\Reclaim\Helper\Logger $klaviyoLogger
     */
    protected $_klaviyoLogger;

    /**
     * Klaviyo scope setting helper
     * @var \Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting
     */
    protected $_klaviyoScopeSetting;

    public function __construct(
        Logger $klaviyoLogger,
        ScopeSetting $klaviyoScopeSetting
    ) {
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
    }

    /**
     * @param string $webhookType
     * @param array $data
     * @param string $klaviyoId
     * @return string
     * @throws Exception
     */
    public function makeWebhookRequest($webhookType, $data, $klaviyoId = null)
    {

        if (!$klaviyoId) {
            $klaviyoId = $this->_klaviyoScopeSetting->getPublicApiKey();
        }
        $url = self::WEBHOOK_URL . '?c=' . $klaviyoId;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Magento-two-signature: ' . $this->createWebhookSecurity($data),
                'Content-Length: ' . strlen(json_encode($data)),
                'Topic: ' . $webhookType
            ),
        ]);

        // Submit the request
        $response = curl_exec($curl);
        $err = curl_errno($curl);

        if ($err) {
            $this->_klaviyoLogger->log(sprintf('Unable to send webhook to %s with data: %s', $url, json_encode($data)));
        }

        // Close cURL session handle
        curl_close($curl);
        return $response;
    }

    /**
     * @param array data
     * @return string
     * @throws Exception
     */
    private function createWebhookSecurity(array $data)
    {
        $webhookSecret = $this->_klaviyoScopeSetting->getWebhookSecret();
        return hash_hmac('sha256', json_encode($data), $webhookSecret);
    }
}
