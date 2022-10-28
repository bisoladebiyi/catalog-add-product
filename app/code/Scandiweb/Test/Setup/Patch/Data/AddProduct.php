<?php

namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\State;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Validation\ValidationException;
use Exception;


class AddProduct implements DataPatchInterface
{
    /**
     * @var ProductInterfaceFactory
     */
    protected ProductInterfaceFactory $productFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var State
     */
    protected State $appState;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var SourceItemInterfaceFactory
     */
    protected SourceItemInterfaceFactory $sourceItem;

    /**
     * @var SourceItemsSaveInterface
     */
    protected SourceItemsSaveInterface $sourceItemsSaveInterface;

    /**
     * @var EavSetup
     */
    protected EavSetup $eavSetup;

    /**
     * @var CategoryLinkManagementInterface 
     */
    protected CategoryLinkManagementInterface  $categoryLink;

    /**
     * @var array
     */
    protected array $sourceItems = [];

    /**
     * Migration patch constructor.
     *
     * @param ProductInterfaceFactory $productFactory,
     * @param ProductRepositoryInterface $productRepository,
     * @param State $appState,
     * @param StoreManagerInterface $storeManager,
     * @param EavSetup $eavSetup,
     * @param SourceItemInterfaceFactory $sourceItem,
     * @param SourceItemsSaveInterface $sourceItemsSaveInterface,
     * @param CategoryLinkManagementInterface $categoryLink,
     */
    public function __construct(
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        State $appState,
        StoreManagerInterface $storeManager,
        EavSetup $eavSetup,
        SourceItemInterfaceFactory $sourceItem,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        CategoryLinkManagementInterface $categoryLink,
    ) {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->appState = $appState;
        $this->storeManager = $storeManager;  
        $this->eavSetup = $eavSetup;   
        $this->sourceItem = $sourceItem;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->categoryLink = $categoryLink;
    }

    /**
     * @return AddProduct|void
     * @throws Exception
     */
	public function apply()
    {
        $this->appState->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

    /**
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     * @throws ValidationException
     */
    public function execute()
    {
        $product = $this->productFactory->create();  // create product
        $prodSKU = 'white_clean_tee';

        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');


        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId($attributeSetId)
            ->setName('White Clean Tee')
            ->setSku($prodSKU)
            ->setPrice(100.20)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED);
        $product = $this->productRepository->save($product);  // add product to repo

        $sourceItem = $this->sourceItem->create();  // create source item
        $sourceItem->setSourceCode('default');
        $sourceItem->setQuantity(10);
        $sourceItem->setSku($product->getSku());
        $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        $this->sourceItems[] = $sourceItem;
        $this->sourceItemsSaveInterface->execute($this->sourceItems);

        $this->categoryLink->assignProductToCategories($product->getSku(), [2]);
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
