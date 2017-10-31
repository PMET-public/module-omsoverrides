<?php


namespace MagentoEse\OmsOverrides\Plugin\Magento\SalesMessageBus\Api\Sales\Data;

class OrderLineInterface
{

    public function afterGetImageUrl(
        \Magento\SalesMessageBus\Api\Sales\Data\OrderLineInterface $subject,
        $result
    ) {
        return str_replace("/pub","",$result);
    }
}
