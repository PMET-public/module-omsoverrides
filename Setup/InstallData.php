<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 */

namespace MagentoEse\OmsOverrides\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallData implements InstallDataInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $resourceConfig;

    /**
     * InstallData constructor.
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     */
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig)
    {
        $this->resourceConfig = $resourceConfig;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->resourceConfig->saveConfig(
            "mcom_configuration/transport/driver", "Service Bus (HTTP)", "default", 0)->saveConfig(
            "mcom_configuration/tax/vat_country", "US", "default", 0)->saveConfig(
            "mcom_configuration/general/store_id", "main_website_store", "default", 0);
    }

}