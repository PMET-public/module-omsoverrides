<?php

namespace MagentoEse\OmsOverrides\CatalogMessageBus\Product;

use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface as MagentoProduct;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\LinkFactory;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogMessageBus\Api\Data\ProductInterface;
use Magento\CatalogMessageBus\Model\LocalizedAndByChannelValue;
use Magento\CatalogMessageBus\Model\Product;
use Magento\CatalogMessageBus\Model\ProductAssociation;
use Magento\CatalogMessageBus\Model\ProductCustomization;
use Magento\CatalogMessageBus\Model\ProductCustomizationExtraprice;
use Magento\CatalogMessageBus\Model\ProductCustomizationOption;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\StoreConfigManagerInterface;

class Mapper extends \Magento\CatalogMessageBus\Product\Mapper
{
    const VISIBILITY_CATALOG = 'catalog';
    const VISIBILITY_SEARCH = 'search';
    const EXPECTED_DATE_FORMAT = 'Y-m-d H:i:s';
    const CONFIGURATION_WEIGHT_UNIT_PATH = 'general/locale/weight_unit';

    /**
     * @var ProductInterfaceFactory
     */
    private $productFactory;

    /**
     * @var LinkFactory
     */
    private $productLinkFactory;

    /**
     * @var OptionFactory
     */
    private $productOptionFactory;

    /**
     * @var ProductCustomOptionValuesInterfaceFactory
     */
    private $productOptionValueFactory;

    /**
     * @var ChildrenConfigurableFinder
     */
    private $childrenConfigurableFinder;

    /**
     * @var StoreConfigManagerInterface
     */
    private $storeConfigManager;

    /**
     * @var ProductRepositoryInterface\Proxy
     */
    private $productRepository;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    private $storeManager;
    /**
     * @param ProductInterfaceFactory                   $productFactory
     * @param ProductLinkInterfaceFactory               $productLinkFactory
     * @param OptionFactory                             $productOptionFactory
     * @param ProductCustomOptionValuesInterfaceFactory $productOptionValueFactory
     * @param ChildrenConfigurableFinder                $childrenConfigurableFinder
     * @param StoreConfigManagerInterface               $storeConfigManager
     * @param ProductRepositoryInterface\Proxy          $productRepository
     * @param ScopeConfigInterface                      $scopeConfig
     */
    public function __construct(
        ProductInterfaceFactory $productFactory,
        ProductLinkInterfaceFactory $productLinkFactory,
        OptionFactory $productOptionFactory,
        ProductCustomOptionValuesInterfaceFactory $productOptionValueFactory,
        \Magento\CatalogMessageBus\Product\ChildrenConfigurableFinder $childrenConfigurableFinder,
        StoreConfigManagerInterface $storeConfigManager,
        ProductRepositoryInterface\Proxy $productRepository,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->productFactory = $productFactory;
        $this->productLinkFactory = $productLinkFactory;
        $this->productOptionFactory = $productOptionFactory;
        $this->productOptionValueFactory = $productOptionValueFactory;
        $this->childrenConfigurableFinder = $childrenConfigurableFinder;
        $this->storeConfigManager = $storeConfigManager;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param MagentoProduct $magentoProduct
     *
     * @return ProductInterface
     */
    public function toSpec(MagentoProduct $magentoProduct)
    {
        $createdAt = $magentoProduct->getCreatedAt() ?: date(self::EXPECTED_DATE_FORMAT);
        $updatedAt = $magentoProduct->getUpdatedAt() ?: date(self::EXPECTED_DATE_FORMAT);

        $specProduct = new Product();

        $specProduct->setType($magentoProduct->getTypeId());
        $specProduct->setSku($magentoProduct->getSku());
        $specProduct->setName($this->getProductNames($magentoProduct));
        $specProduct->setAttributeSet($magentoProduct->getAttributeSetId());
        $specProduct->setCreatedAt($this->formatDate($createdAt));
        $specProduct->setModifiedAt($this->formatDate($updatedAt));
        $specProduct->setEnabled($magentoProduct->getStatus());
        $specProduct->setVisibility($this->toSpecVisibility($magentoProduct));
        $specProduct->setAssociations($this->toSpecAssociations($magentoProduct));
        $specProduct->setCustomizations($this->toSpecCustomizations($magentoProduct));

        $this->addCustomAttributes($magentoProduct, $specProduct);

        $this->setChildrenProducts($magentoProduct, $specProduct);

        return $specProduct;
    }

    /**
     * @param MagentoProduct $magentoProduct
     * @param Product        $specProduct
     */
    private function setChildrenProducts($magentoProduct, $specProduct)
    {
        $childrenProducts = $this->childrenConfigurableFinder->getChildren($magentoProduct);

        if (count($childrenProducts)) {
            $childrenSkus = array_map(function ($children) {
                return $children['sku'];
            }, $childrenProducts);

            $specProduct->setChildrenSkus($childrenSkus);
        }
    }

    /**
     * @param MagentoProduct $magentoProduct
     *
     * @return array
     */
    private function toSpecVisibility(MagentoProduct $magentoProduct)
    {
        if ($magentoProduct->getVisibility() == Visibility::VISIBILITY_BOTH) {
            return [self::VISIBILITY_CATALOG, self::VISIBILITY_SEARCH];
        }

        if ($magentoProduct->getVisibility() == Visibility::VISIBILITY_IN_CATALOG) {
            return [self::VISIBILITY_CATALOG];
        }

        if ($magentoProduct->getVisibility() == Visibility::VISIBILITY_IN_SEARCH) {
            return [self::VISIBILITY_SEARCH];
        }

        return [];
    }

    /**
     * @param MagentoProduct $magentoProduct
     *
     * @return ProductAssociation[]
     */
    private function toSpecAssociations(MagentoProduct $magentoProduct)
    {
        $links = [];
        foreach ($magentoProduct->getProductLinks() as $link) {
            $type = $link->getLinkType();

            if (!isset($links[$type])) {
                $links[$type] = [];
            }

            $links[$type][] = $link->getLinkedProductSku();
        }

        $associations = [];
        foreach ($links as $type => $link) {
            $productAssociation = new ProductAssociation();
            $productAssociation->setType($type);
            $productAssociation->setProducts($link);
            $associations[] = $productAssociation;
        }

        return $associations;
    }

    /**
     * @param MagentoProduct $magentoProduct
     *
     * @return ProductCustomization[]
     */
    private function toSpecCustomizations(MagentoProduct $magentoProduct)
    {
        $customizations = [];
        $magentoOptions = $magentoProduct->getOptions();
        if (empty($magentoOptions)) {
            return [];
        }

        foreach ($magentoOptions as $option) {
            $extraPrice = new ProductCustomizationExtraprice();
            $extraPrice->setType($option->getPriceType());
            $extraPrice->setValue($option->getPrice());

            $customization = new ProductCustomization();
            $customization->setCode($option->getOptionId());
            $customization->setInputType($option->getType());
            $customization->setExtraPrice($extraPrice);
            $customization->setConstraint(array_filter([
                'file_extension' => $option->getFileExtension(),
                'max_image_width' => $option->getImageSizeX(),
                'max_image_height' => $option->getImageSizeY(),
                'required' => $option->getIsRequire(),
                'max_length' => $option->getMaxCharacters(),
            ]));

            $customization->setTitle([
                (new LocalizedAndByChannelValue())
                    ->setValue($option->getTitle()),
            ]);

            if ($option->getValues() !== null) {
                $customization->setOptions(array_map([$this, 'toSpecCustomizationOption'], $option->getValues()));
            }

            $customizations[] = $customization;
        }

        return $customizations;
    }

    /**
     * @param MagentoProduct $magentoProduct
     * @param Product        $specProduct
     */
    private function addCustomAttributes(MagentoProduct $magentoProduct, Product $specProduct)
    {
        if ($customAttributes = $magentoProduct->getCustomAttributes()) {
            foreach ($customAttributes as $customAttribute) {

                if (is_array($customAttribute->getValue())) {
                    continue;
                }
                $f=$customAttribute->getAttributeCode();
                if($customAttribute->getAttributeCode()=="image"||$customAttribute->getAttributeCode()=="small_image"||$customAttribute->getAttributeCode()=="thumbnail"){
                    $specProduct->setCustomAttribute(
                        $customAttribute->getAttributeCode(),
                        $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . "catalog/product" . $magentoProduct->getData('image')

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

            $weightUnit = $this->scopeConfig->getValue(self::CONFIGURATION_WEIGHT_UNIT_PATH);

            $specProduct->setCustomAttribute('weight_unit', $weightUnit);
        }
    }

    /**
     * @param ProductCustomOptionValuesInterface $value
     *
     * @return ProductCustomizationOption
     */
    private function toSpecCustomizationOption(ProductCustomOptionValuesInterface $value)
    {
        $extraPrice = new ProductCustomizationExtraprice();
        $extraPrice->setType($value->getPriceType());
        $extraPrice->setValue($value->getPrice());
        $productCustomizationOption = new ProductCustomizationOption();
        $productCustomizationOption->setName([
            (new LocalizedAndByChannelValue())
                ->setValue($value->getTitle()),
        ]);
        $productCustomizationOption->setExtraPrice($extraPrice);
        $productCustomizationOption->setSku($value->getSku());
        $productCustomizationOption->setSortOrder($value->getSortOrder());

        return $productCustomizationOption;
    }

    /**
     * @param string $date
     *
     * @return string
     */
    private function formatDate($date)
    {
        return \DateTime::createFromFormat(self::EXPECTED_DATE_FORMAT, $date)
            ->format(\DateTime::ATOM);
    }

    /**
     * @param MagentoProduct $magentoProduct
     *
     * @return array
     */
    private function getProductNames(MagentoProduct $magentoProduct)
    {
        $names = [];

        $storeProduct = $this->productRepository
            ->getById($magentoProduct->getId(), false, 0, true);

        $names[] = (new LocalizedAndByChannelValue())
            ->setValue($storeProduct->getName());

        foreach ($this->storeConfigManager->getStoreConfigs() as $config) {
            $storeProduct = $this->productRepository
                ->getById($magentoProduct->getId(), false, (int) $config->getId(), true);

            $names[] = (new LocalizedAndByChannelValue())
                ->setValue($storeProduct->getName())
                ->setLocale($config->getLocale())
                ->setChannel($config->getCode());
        }

        return $names;
    }
}
