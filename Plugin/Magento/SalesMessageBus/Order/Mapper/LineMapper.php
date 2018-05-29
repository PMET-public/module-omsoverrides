<?php


namespace MagentoEse\OmsOverrides\Plugin\Magento\SalesMessageBus\Order\Mapper;

use \Magento\Sales\Api\OrderRepositoryInterface;

class LineMapper
{

    public function afterGetImageUrl(
        \Magento\SalesMessageBus\Order\Mapper\LineMapper $subject,
        $result
    ) {
        return str_replace("/pub","",$result);
    }
    public function afterToSpecification(\Magento\SalesMessageBus\Order\Mapper\LineMapper $subject,  \Magento\SalesMessageBus\Model\Sales\OrderLine $result)
    {
        //This adds data from the MagentoEse_InStorePickup module to be used by OMS
            $storeId = 'Store_80';
            $result->setPickupLocation($storeId);
        return $result;
    }
    private function getOrderExtensionDependency()

    {

        $orderExtension = \Magento\Framework\App\ObjectManager::getInstance()->get(
            '\Magento\Sales\Api\Data\OrderExtension'
        );

        return $orderExtension;

    } 
}
