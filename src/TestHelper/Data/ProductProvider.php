<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\TestHelper\Data;

use Faker\Factory;
use Faker\Generator;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Setup\CategorySetup;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\Search\SearchCriteriaFactory;
use RuntimeException;
use Zend\Hydrator\ClassMethods as ObjectHydrator;

class ProductProvider
{
    /**
     * Product default stock qty
     */
    const DEFAULT_STOCK_QTY = 100;

    /**
     * @var Generator
     */
    private $faker;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductInterfaceFactory
     */
    private $productFactory;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var SearchCriteriaFactory
     */
    private $searchCriteriaFactory;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var CategorySetup
     */
    private $categorySetup;

    /**
     * @var CategoryLinkManagementInterface
     */
    private $categoryLinkManagement;

    /**
     * @var ObjectHydrator
     */
    private $objectHydrator;
    /**
     * @var CategoryProvider
     */
    private $categoryProvider;

    /**
     * CategoryDataProvider constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     * @param ProductInterfaceFactory $productFactory
     * @param StockRegistryInterface $stockRegistry
     * @param SearchCriteriaFactory $searchCriteriaFactory
     * @param EavConfig $eavConfig
     * @param CategorySetup $categorySetup
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param ObjectHydrator $objectHydrator
     * @param CategoryProvider $categoryProvider
     * @internal param CategoryProvider $categoryProvider
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductInterfaceFactory $productFactory,
        StockRegistryInterface $stockRegistry,
        SearchCriteriaFactory $searchCriteriaFactory,
        EavConfig $eavConfig,
        CategorySetup $categorySetup,
        CategoryLinkManagementInterface $categoryLinkManagement,
        ObjectHydrator $objectHydrator,
        CategoryProvider $categoryProvider
    )
    {
        $this->faker = Factory::create();
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->stockRegistry = $stockRegistry;
        $this->searchCriteriaFactory = $searchCriteriaFactory;
        $this->eavConfig = $eavConfig;
        $this->categorySetup = $categorySetup;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->objectHydrator = $objectHydrator;
        $this->categoryProvider = $categoryProvider;
    }

    /**
     * @return $this
     */
    public function clearData(): self
    {
        $criteria = $this->searchCriteriaFactory->create();
        $products = $this->productRepository->getList($criteria)->getItems();
        foreach ($products as $product) {
            $this->productRepository->delete($product);
        }

        return $this;
    }

    /**
     * @param string $set
     * @return int
     */
    protected function getAttributeSetId(string $set = 'Default'): int
    {
        return (int) $this->categorySetup->getAttributeSetId(Product::ENTITY, $set);
    }

    /**
     * @param array $data
     * @return ProductInterface
     */
    public function createProduct(array $data = []): ProductInterface
    {
        /** @var ProductInterface $product */
        $product = $this->productFactory->create();

        // Set product defaults
        $product->setSku('test-' . $this->faker->uuid);
        $product->setName($this->faker->name);
        $product->setTypeId(Product\Type::TYPE_SIMPLE);
        $product->setVisibility(Product\Visibility::VISIBILITY_BOTH);
        $product->setPrice($this->faker->randomNumber(2));
        $product->setAttributeSetId($this->getAttributeSetId());
        $product->setStatus(Product\Attribute\Source\Status::STATUS_ENABLED);

        // Overwrite with provided data
        $this->objectHydrator->hydrate($data, $product);

        // Save product
        $this->productRepository->save($product);

        // Ensure product qty
        $data['qty'] = $data['qty'] ?? [self::DEFAULT_STOCK_QTY];
        $this->updateStockItem($product, $data);

        // Assign product to categories
        $categoryIds = $data['category_ids'] ?? $this->categoryProvider->getDefaultCategoryIds();
        $this->categoryLinkManagement->assignProductToCategories($product->getSku(), $categoryIds);

        return $product;
    }

    /**
     * @param ProductInterface $product
     * @param array $data
     * @return StockItemInterface
     */
    public function updateStockItem(ProductInterface $product, array $data): StockItemInterface
    {
        $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
        $this->objectHydrator->hydrate($data, $stockItem);
        $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);
        return $stockItem;
    }

    /**
     * @param string $code
     * @return Attribute
     */
    protected function getProductAttribute(string $code): Attribute
    {
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $code);
        if (!$attribute instanceof Attribute) {
            throw new RuntimeException('Invalid attribute type returned by eav config');
        }
        return $attribute;
    }

    /**
     * Fetches or creates option id for product attribute
     *
     * @param string $code
     * @param string $label
     * @return int
     */
    protected function getAttributeOptionId(string $code, string $label): int
    {
        $attribute = $this->getProductAttribute($code);
        $options = $attribute->getOptions();
        foreach ($options as $option) {
            if ($option->getLabel() === $label) {
                return (int) $option->getValue();
            }
        }
    }
}