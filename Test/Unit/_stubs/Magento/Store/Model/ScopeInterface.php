<?php

namespace Magento\Store\Model;

if (!interface_exists(\Magento\Store\Model\ScopeInterface::class, false)) {
    interface ScopeInterface
    {
        const SCOPE_STORES = 'stores';
        const SCOPE_GROUPS = 'groups';
        const SCOPE_WEBSITES = 'websites';
        const SCOPE_STORE = 'store';
        const SCOPE_GROUP = 'group';
        const SCOPE_WEBSITE = 'website';
    }
}
