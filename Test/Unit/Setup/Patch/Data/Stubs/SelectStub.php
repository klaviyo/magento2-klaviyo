<?php

namespace Klaviyo\Reclaim\Test\Unit\Setup\Patch\Data\Stubs;

/**
 * Query-builder shim used by the test mock. Cannot reuse
 * Magento\Framework\DB\Select directly because the real Magento class
 * (when autoloaded in a full Magento test environment) requires
 * constructor args, which breaks the test stub instantiation.
 */
class SelectStub
{
    public function from($table, $cols = '*')
    {
        return $this;
    }
    public function where($condition, $value = null)
    {
        return $this;
    }
}
