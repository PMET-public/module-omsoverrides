<?php
/**
 * Created by PhpStorm.
 * User: jbritts
 * Date: 10/31/17
 * Time: 11:51 AM
 */

namespace MagentoEse\OmsOverrides\CatalogMessageBus\Product;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface as MagentoProduct;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\CatalogMessageBus\Model\Product;
use Magento\CatalogMessageBus\Product\ChildrenConfigurableFinder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\StoreConfigManagerInterface;

class Mapper extends \Magento\CatalogMessageBus\Product\Mapper
{

    public function __construct(ProductInterfaceFactory $productFactory,
                                ProductLinkInterfaceFactory $productLinkFactory,
                                OptionFactory $productOptionFactory,
                                ProductCustomOptionValuesInterfaceFactory $productOptionValueFactory,
                                ChildrenConfigurableFinder $childrenConfigurableFinder,
                                StoreConfigManagerInterface $storeConfigManager,
                                ProductRepositoryInterface\Proxy $productRepository,
                                ScopeConfigInterface $scopeConfig)
    {
        parent::__construct($productFactory, $productLinkFactory, $productOptionFactory, $productOptionValueFactory, $childrenConfigurableFinder, $storeConfigManager, $productRepository, $scopeConfig);
        $this->scopeingConfig = $scopeConfig;
    }

    /**
     * @param MagentoProduct $magentoProduct
     * @param Product        $specProduct
     */
    private function addCustomAttributes(MagentoProduct $magentoProduct, Product $specProduct)
    {
        if ($customAttributes = $magentoProduct->getCustomAttributes()) {
            foreach ($customAttributes as $customAttribute) {
                /* do not serialize array value */
                if (is_array($customAttribute->getValue())) {
                    continue;
                }
                if(strpos($customAttribute->getAttributeCode(),"image")){
                    $specProduct->setCustomAttribute(
                        $customAttribute->getAttributeCode(),
                        str_replace("/pub","",$magentoProduct->getData('image'))
                    );
                }else{
                    $specProduct->setCustomAttribute(
                        $customAttribute->getAttributeCode(),
                        $customAttribute->getValue()
                    );
                }

            }
        }

        if ($magentoProduct->getWeight()) {
            $specProduct->setCustomAttribute('weight', $magentoProduct->getWeight());

            $weightUnit = $this->scopeingConfig->getValue(self::CONFIGURATION_WEIGHT_UNIT_PATH);

            $specProduct->setCustomAttribute('weight_unit', $weightUnit);
        }
    }
}