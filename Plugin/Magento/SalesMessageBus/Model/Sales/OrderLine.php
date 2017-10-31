<?php


namespace MagentoEse\OmsOverrides\Plugin\Magento\SalesMessageBus\Model\Sales;

class OrderLine
{

    public function afterGetImageUrl(
        \Magento\SalesMessageBus\Model\Sales\OrderLine $subject,
        $result
    ) {
        return str_replace("/pub","",$result);
    }
}
