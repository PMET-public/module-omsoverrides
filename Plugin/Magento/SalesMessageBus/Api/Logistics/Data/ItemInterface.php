<?php


namespace MagentoEse\OmsOverrides\Plugin\Magento\SalesMessageBus\Api\Logistics\Data;

class ItemInterface
{

    public function afterGetImageUrl(
        \Magento\SalesMessageBus\Api\Logistics\Data\ItemInterface $subject,
        $result
    ) {
        return str_replace("/pub","",$result);
    }
}
