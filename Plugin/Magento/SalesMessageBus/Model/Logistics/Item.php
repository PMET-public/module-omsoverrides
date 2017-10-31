<?php


namespace MagentoEse\OmsOverrides\Plugin\Magento\SalesMessageBus\Model\Logistics;

class Item
{

    public function afterGetImageUrl(
        \Magento\SalesMessageBus\Model\Logistics\Item $subject,
        $result
    ) {
        return str_replace("/pub","",$result);
    }
}
