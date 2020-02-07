<?php
namespace Klaviyo\Reclaim\Setup;
  
use \Magento\Framework\Setup\InstallDataInterface;
use \Magento\Framework\Setup\ModuleDataSetupInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
use \Magento\Authorization\Model\UserContextInterface;
use \Magento\Authorization\Model\RoleFactory;
use \Magento\Authorization\Model\RulesFactory;
 
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
     * Logging instance
     * 
     * @var \Klaviyo\Reclaim\Logger\Logger
     */
    protected $_logger;
 
    const KLAVIYO_ROLE_NAME = 'Klaviyo';

    /**
     * Init
     *
     * @param RoleFactory $roleFactory
     * @param RulesFactory $rulesFactory
     * @param \Klaviyo\Reclaim\Logger\Logger $_logger
     */
    public function __construct(
        RoleFactory $roleFactory,
        RulesFactory $rulesFactory,
        \Klaviyo\Reclaim\Logger\Logger $_logger
    ) {
        $this->roleFactory = $roleFactory;
        $this->rulesFactory = $rulesFactory;
        $this->_logger = $_logger;
    }
 
    public function install(
        ModuleDataSetupInterface $setup, 
        ModuleContextInterface $context
    ) {
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
            $this->_logger->info('RULE CREATION ISSUE: ' . $ex->getMessage());
        }
    }
}
