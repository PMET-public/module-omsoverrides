<?php


namespace MagentoEse\OmsOverrides\Plugin\Magento\SalesMessageBus\Order\Mapper;

class LineMapper
{

    public function afterGetImageUrl(
        \Magento\SalesMessageBus\Order\Mapper\LineMapper $subject,
        $result
    ) {
        return str_replace("/pub","",$result);
    }
}
