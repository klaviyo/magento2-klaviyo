<?php

namespace KlaviyoV3Sdk;

class Klaviyo
{
    /**
     * @var string
     */
    protected $private_key;

    /**
     * @var string
     */
    protected $public_key;

    /**
     * @var string
     */
    const VERSION  = '1.0.0';

    /**
     * Constructor for Klaviyo V3 SDK wrapper
     *
     * @param string $private_key Private Key for Klaviyo account
     * @param string $public_key Public Key for Klaviyo Account
     */
    public function __construct($private_key, $public_key)
    {
        $this->private_key = $private_key;
        $this->public_key = $public_key;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->private_key;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->public_key;
    }
}