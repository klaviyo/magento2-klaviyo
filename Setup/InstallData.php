<?php
namespace Klaviyo\Reclaim\Setup;
  
use \Magento\Framework\Setup\InstallDataInterface;
use \Magento\Framework\Setup\ModuleDataSetupInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Klaviyo\Reclaim\Helper\Logger;
 
class InstallData implements InstallDataInterface
{
    /**
     * Logging helper
     * 
     * @var Logger
     */
    protected $_klaviyoLogger;

    /**
     * @var \Magento\Framework\App\State 
     */
    protected $_state;
    
    /**
     * Init
     *
     * @param Logger $klaviyoLogger
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        Logger $klaviyoLogger,
        \Magento\Framework\App\State $state
    ) {
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_state = $state;
    }
 
    public function install(
        ModuleDataSetupInterface $setup, 
        ModuleContextInterface $context
    ) {
        try{
            $this->_state->getAreaCode();
        }
        catch (\Magento\Framework\Exception\LocalizedException $ex) {
            $this->_state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        }
    }
}
