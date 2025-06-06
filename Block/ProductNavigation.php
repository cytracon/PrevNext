<?php
namespace Cytracon\PrevNext\Block;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;

class ProductNavigation extends Template
{
    protected $productCollectionFactory;
    protected $registry;
    protected $logger;

    public function __construct(
        Template\Context $context,
        CollectionFactory $productCollectionFactory,
        \Magento\Framework\Registry $registry,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->registry = $registry;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    public function getPreviousProduct()
    {
        return $this->getAdjacentProduct(-1);
    }

    public function getNextProduct()
    {
        return $this->getAdjacentProduct(1);
    }

    protected function getAdjacentProduct($offset)
    {
        $currentProduct = $this->registry->registry('current_product');
        if (!$currentProduct) {
            $this->logger->debug('ProductNavigation: Kein aktuelles Produkt gefunden.');
            return null;
        }

        $category = $this->getCurrentCategory();
        if (!$category) {
            $this->logger->debug('ProductNavigation: Keine Kategorie gefunden für Produkt ID ' . $currentProduct->getId());
            return null;
        }

        $collection = $this->productCollectionFactory->create()
            ->addCategoryFilter($category)
            ->addAttributeToSelect(['name', 'url_key'])
            ->setOrder('entity_id', 'ASC');

        $products = $collection->getItems();
        if (empty($products)) {
            $this->logger->debug('ProductNavigation: Keine Produkte in Kategorie ID ' . $category->getId());
            return null;
        }

        $productIds = array_keys($products);
        $currentIndex = array_search($currentProduct->getId(), $productIds);

        $adjacentIndex = $currentIndex + $offset;
        if (isset($productIds[$adjacentIndex])) {
            return $products[$productIds[$adjacentIndex]];
        }

        return null;
    }

    protected function getCurrentCategory()
    {
        $currentProduct = $this->registry->registry('current_product');
        if ($currentProduct) {
            $categories = $currentProduct->getCategoryCollection()->setPageSize(1);
            return $categories->getFirstItem();
        }
        return null;
    }
}
