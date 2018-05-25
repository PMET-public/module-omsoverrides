<?php


namespace MagentoEse\OmsOverrides\Plugin\Magento\SalesMessageBus\Order\Mapper;

use \Magento\Sales\Api\OrderRepositoryInterface;

class LineMapper
{

    /** @var OrderRepositoryInterface  */
    protected $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function afterGetImageUrl(
        \Magento\SalesMessageBus\Order\Mapper\LineMapper $subject,
        $result
    ) {
        return str_replace("/pub","",$result);
    }
    public function afterToSpecification(\Magento\SalesMessageBus\Order\Mapper\LineMapper $subject,  \Magento\SalesMessageBus\Model\Sales\OrderLine $result)
    {
        //This adds data from the MagentoEse_InStorePickup module to be used by OMS
        $order = $this->orderRepository->get($result->getId());
        $storeName = $order->getInstorepickupStoreLocationName();
        $storeId = $order->getInstorepickupStoreLocationId();
        $storeCity =$order->getInstorepickupStoreLocationCity();
        $storeState =$order->getInstorepickupStoreLocationState();
        $storePostalCode=$order->getInstorepickupStoreLocationPostalCode();
        $result->setPickupLocation($storeName);
        //$result->setCustomAttribute('yupyup','ValueOfCustomAttribute');
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
