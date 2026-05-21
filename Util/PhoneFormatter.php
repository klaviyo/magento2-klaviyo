<?php

declare(strict_types=1);

namespace Klaviyo\Reclaim\Util;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneFormatter
{
    public function formatE164(?string $phone, ?string $isoCountry): ?string
    {
        if (empty($phone) || empty($isoCountry)) {
            return null;
        }
        try {
            $util = PhoneNumberUtil::getInstance();
            $parsed = $util->parse($phone, $isoCountry);
            if (!$util->isValidNumber($parsed)) {
                return null;
            }
            return $util->format($parsed, PhoneNumberFormat::E164);
        } catch (\Exception $e) {
            return null;
        }
    }
}
