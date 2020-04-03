<?php
namespace Klaviyo\Reclaim\Setup;
  
use \Magento\Framework\Setup\InstallDataInterface;
use \Magento\Framework\Setup\ModuleDataSetupInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
use \Magento\Authorization\Model\UserContextInterface;
use \Magento\Authorization\Model\RoleFactory;
use \Magento\Authorization\Model\RulesFactory;
use \Klaviyo\Reclaim\Helper\Logger;
 
class InstallData implements InstallDataInterface
{
    /**
     * RoleFactory
     *
     * @var roleFactory
     */
    private $roleFactory;
 
    /**
     * RulesFactory
     *
     * @var rulesFactory
     */
    private $rulesFactory;

    /**
     * Logging helper
     * 
     * @var Logger
     */
    protected $_klaviyoLogger;
 
    const KLAVIYO_ROLE_NAME = 'Klaviyo';

    /**
     * Init
     *
     * @param RoleFactory $roleFactory
     * @param RulesFactory $rulesFactory
     * @param Logger $klaviyoLogger
     */
    public function __construct(
        RoleFactory $roleFactory,
        RulesFactory $rulesFactory,
        Logger $klaviyoLogger
    ) {
        $this->roleFactory = $roleFactory;
        $this->rulesFactory = $rulesFactory;
        $this->_klaviyoLogger = $klaviyoLogger;
    }
 
    public function install(
        ModuleDataSetupInterface $setup, 
        ModuleContextInterface $context
    ) {
        //Klaviyo log file creation
        $path = $this->_klaviyoLogger->getPath();
        if (!file_exists($path)) {
            fopen($path, 'w');
        }
        chmod($path, 0644);
        
        //Klaviyo role creation
        $role = $this->roleFactory->create();
        $role->setName(self::KLAVIYO_ROLE_NAME)
                ->setPid(0)
                ->setRoleType(RoleGroup::ROLE_TYPE) 
                ->setUserType(UserContextInterface::USER_TYPE_ADMIN);
        $role->save();
 
        $resource = [
            'Magento_Backend::all'
        ];
        try {
            $this->rulesFactory->create()
                ->setRoleId($role->getId())
                ->setResources($resource)
                ->saveRel();
        } catch (\Exception $ex) {
            $this->_klaviyoLogger->log('RULE CREATION ISSUE: ' . $ex->getMessage());
        }
    }
}
