<?php

namespace Magento\Framework\App;

if (!class_exists(\Magento\Framework\App\Area::class, false)) {
    class Area
    {
        const AREA_GLOBAL = 'global';
        const AREA_FRONTEND = 'frontend';
        const AREA_ADMINHTML = 'adminhtml';
        const AREA_DOC = 'doc';
        const AREA_CRONTAB = 'crontab';
        const AREA_WEBAPI_REST = 'webapi_rest';
        const AREA_WEBAPI_SOAP = 'webapi_soap';
        const AREA_GRAPHQL = 'graphql';
        const AREA_ADMIN = 'admin';
    }
}
