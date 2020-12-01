<?php

namespace Klaviyo\Reclaim\Helper;

use Exception;
use \Klaviyo\Reclaim\Helper\ScopeSetting;

class Webhook extends \Magento\Framework\App\Helper\AbstractHelper
{
    const USER_AGENT = 'Klaviyo/MagentoTwo/Webhook';
    const WEBHOOK_URL = 'https://www.klaviyo.com/api/webhook/integration/magento_two';

    /**
     * Klaviyo scope setting helper
     * @var \Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting
     */
    protected $_klaviyoScopeSetting;

    public function __construct(
        ScopeSetting $klaviyoScopeSetting
    ) {
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
    }

    /**
     * @param string $webhookType
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function makeWebhookRequest(string $webhookType, array $data)
    {
        $data['webhook_type'] = $webhookType;

        $url = self::WEBHOOK_URL . '?c=' . $this->_klaviyoScopeSetting->getPublicApiKey();

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
                'Content-Length: '. strlen(json_encode($data))
            ),
        ]);

        // Submit the request
        $response = curl_exec($curl);
        $err = curl_errno($curl);

        if ($err) {
            throw new Exception(curl_error($curl));
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

