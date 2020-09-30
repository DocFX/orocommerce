<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface as Record;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataEvent;
use Oro\Bundle\ShoppingListBundle\Validator\LineItemViolationsProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Populates line item records by required data.
 */
class FrontendLineItemsGridEventListener
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var NumberFormatter */
    private $numberFormatter;

    /** @var UnitLabelFormatterInterface */
    private $unitLabelFormatter;

    /** @var UnitValueFormatterInterface */
    private $unitValueFormatter;

    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var FrontendProductPricesDataProvider */
    private $productPricesDataProvider;

    /** @var ConfigurableProductProvider */
    private $configurableProductProvider;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var LineItemViolationsProvider */
    private $violationsProvider;

    /** @var PreloadingManager */
    private $preloadingManager;

    /** @var array */
    private $fieldsToPreload = [
        'parentProduct' => [
            'names' => [],
            'images' => [
                'image' => [
                    'digitalAsset' => [
                        'titles' => [],
                        'sourceFile' => [
                            'digitalAsset' => [],
                        ],
                    ]
                ],
                'types' => [],
            ],
        ],
        'product' => [
            'isUpcoming' => [],
            'highlightLowInventory' => [],
            'minimumQuantityToOrder' => [],
            'maximumQuantityToOrder' => [],
            'names' => [],
            'images' => [
                'image' => [
                    'digitalAsset' => [
                        'titles' => [],
                        'sourceFile' => [
                            'digitalAsset' => [],
                        ],
                    ]
                ],
                'types' => [],
            ],
            'unitPrecisions' => [],
            'category' => [],
        ],
    ];

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param EventDispatcherInterface $eventDispatcher
     * @param NumberFormatter $numberFormatter
     * @param UnitLabelFormatterInterface $unitLabelFormatter
     * @param UnitValueFormatterInterface $unitValueFormatter
     * @param AttachmentManager $attachmentManager
     * @param FrontendProductPricesDataProvider $productPricesDataProvider
     * @param ConfigurableProductProvider $configurableProductProvider
     * @param LocalizationHelper $localizationHelper
     * @param LineItemViolationsProvider $violationsProvider
     * @param PreloadingManager $preloadingManager
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        EventDispatcherInterface $eventDispatcher,
        NumberFormatter $numberFormatter,
        UnitLabelFormatterInterface $unitLabelFormatter,
        UnitValueFormatterInterface $unitValueFormatter,
        AttachmentManager $attachmentManager,
        FrontendProductPricesDataProvider $productPricesDataProvider,
        ConfigurableProductProvider $configurableProductProvider,
        LocalizationHelper $localizationHelper,
        LineItemViolationsProvider $violationsProvider,
        PreloadingManager $preloadingManager
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->eventDispatcher = $eventDispatcher;
        $this->numberFormatter = $numberFormatter;
        $this->unitLabelFormatter = $unitLabelFormatter;
        $this->unitValueFormatter = $unitValueFormatter;
        $this->attachmentManager = $attachmentManager;
        $this->productPricesDataProvider = $productPricesDataProvider;
        $this->configurableProductProvider = $configurableProductProvider;
        $this->localizationHelper = $localizationHelper;
        $this->violationsProvider = $violationsProvider;
        $this->preloadingManager = $preloadingManager;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event): void
    {
        $lineItems = $this->getLineItems($event);
        if (!$lineItems) {
            return;
        }

        $matchedPrices = $this->productPricesDataProvider->getProductsMatchedPrice($lineItems);
        $errors = $this->violationsProvider->getLineItemViolationLists($lineItems);
        $identifiedLineItems = $this->getIdentifiedLineItems($lineItems);

        foreach ($event->getRecords() as $record) {
            /** @var LineItem[] $recordLineItems */
            $recordLineItems = array_intersect_key(
                $identifiedLineItems,
                array_flip(explode(',', $this->getRowId($record)))
            );
            $record->setValue('id', $this->getRowId($record));

            if ($record->getValue('isConfigurable')) {
                $this->processConfigurableProduct($record, $recordLineItems, $matchedPrices, $errors);
            } else {
                $this->processSimpleProduct($record, reset($recordLineItems), $matchedPrices, $errors);
            }
        }
    }

    /**
     * @param Record $record
     * @return string
     */
    private function getRowId(Record $record): string
    {
        return $record->getValue('allLineItemsIds') ?: $record->getValue('id');
    }

    /**
     * @param Record $record
     * @param LineItem $item
     * @param array $prices
     * @param array $errors
     */
    private function processSimpleProduct(Record $record, LineItem $item, array $prices, array $errors): void
    {
        $product = $item->getProduct();

        $productId = $product->getId();
        $record->setValue('productId', $productId);

        $qty = $item->getQuantity();
        $record->setValue('quantity', $qty);

        $unit = $item->getUnit();
        $record->setValue('unit', $unit->getCode());
        $record->setValue('units', $this->getProductUnits($product, $unit));

        $status = $product->getInventoryStatus();
        $record->setValue('inventoryStatus', ['name' => $status->getId(), 'label' => $status->getName()]);

        $record->setValue('name', $this->localizationHelper->getLocalizedValue($product->getNames()));
        $record->setValue('notes', $item->getNotes());
        $record->setValue(
            'link',
            $this->urlGenerator->generate('oro_product_frontend_product_view', ['id' => $productId])
        );
        $record->setValue(
            'delete_link',
            $this->urlGenerator->generate(
                'oro_api_shopping_list_frontend_delete_line_item',
                ['id' => $item->getId()]
            )
        );

        $event = new LineItemDataEvent([$item]);
        $this->eventDispatcher->dispatch($event, LineItemDataEvent::NAME);

        foreach ($event->getDataForLineItem($item->getId()) as $name => $value) {
            $record->setValue($name, $value);
        }

        $unitCode = $unit->getCode();
        /** @var Price $productPrice */
        $productPrice = $prices[$productId][$unitCode] ?? null;
        if ($productPrice) {
            $price = $productPrice->getValue();
            $currency = $productPrice->getCurrency();
            $subtotal = $price * $qty;

            $discount = $record->getValue('discountValue');
            if ($discount) {
                $record->setValue('initial_subtotal', $this->numberFormatter->formatCurrency($subtotal, $currency));
                $subtotal -= $discount;
            }

            $record->setValue('price', $this->numberFormatter->formatCurrency($price, $currency));
            $record->setValue('subtotal', $this->numberFormatter->formatCurrency($subtotal, $currency));
        }

        /** @var ProductImage $image */
        $image = $product->getImagesByType('listing')->first();
        if ($image) {
            $record->setValue(
                'image',
                $this->attachmentManager->getFilteredImageUrl($image->getImage(), 'product_small')
            );
        }

        $record->setValue('errors', $this->getErrors($errors, $product->getSku(), $unitCode));
    }

    /**
     * @param Record $record
     * @param LineItem[] $items
     * @param array $prices
     * @param array $errors
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processConfigurableProduct(Record $record, array $items, array $prices, array $errors): void
    {
        $rowQuantity = $rowSubtotal = $rowDiscount = 0.0;
        $currency = null;
        $data = [];
        $displayed = explode(',', $record->getValue('displayedLineItemsIds'));

        $event = new LineItemDataEvent($items);
        $this->eventDispatcher->dispatch($event, LineItemDataEvent::NAME);

        $lineItem = reset($items);
        $parentProduct = $lineItem->getParentProduct() ?: $lineItem->getProduct();
        $name = (string)$this->localizationHelper->getLocalizedValue($parentProduct->getNames());

        foreach ($items as $item) {
            $product = $item->getProduct();
            $quantity = $item->getQuantity();
            $rowQuantity += $quantity;
            $unit = $item->getProductUnit();
            $productStatus = $product->getInventoryStatus();
            $itemData = array_merge(
                [
                    'id' => $item->getId(),
                    'productId' => $product->getId(),
                    'sku' => $product->getSku(),
                    'name' => $name,
                    'quantity' => $quantity,
                    'unit' => $item->getProductUnitCode(),
                    'units' => $this->getProductUnits($product, $unit),
                    'inventoryStatus' => ['name' => $productStatus->getId(), 'label' => $productStatus->getName()],
                    'notes' => $item->getNotes(),
                    'price' => null,
                    'subtotal' => null, 'discount' => null, 'total' => null,
                    'productConfiguration' => $this->getConfigurableProducts($item),
                    'errors' => $this->getErrors($errors, $product->getSku(), $unit->getCode()),
                    'filteredOut' => !in_array($item->getId(), $displayed, false),
                    'delete_link' => $this->urlGenerator->generate(
                        'oro_api_shopping_list_frontend_delete_line_item',
                        ['id' => $item->getId(), 'onlyCurrent' => true]
                    ),
                ],
                $event->getDataForLineItem($item->getId())
            );

            $productPrice = $prices[$product->getId()][$unit->getCode()] ?? null;
            if ($productPrice) {
                $price = $productPrice->getValue();
                $currency = $productPrice->getCurrency();

                $subtotal = $price * $quantity;
                if ($rowSubtotal !== null) {
                    $rowSubtotal += $subtotal;
                }

                $discount = $itemData['discountValue'] ?? 0.0;
                if ($discount) {
                    $itemData['initial_subtotal'] = $this->numberFormatter->formatCurrency($subtotal, $currency);
                    $rowDiscount += $discount;
                    $subtotal -= $discount;
                }

                $itemData['price'] = $this->numberFormatter->formatCurrency($price, $currency);
                $itemData['subtotal'] = $this->numberFormatter->formatCurrency($subtotal, $currency);
            } else {
                $rowSubtotal = null;
            }

            $image = $product->getImagesByType('listing')->first();
            if ($image) {
                $itemData['image'] = $this->attachmentManager->getFilteredImageUrl($image->getImage(), 'product_small');
            }

            $itemData['action_configuration'] = array_merge(
                $itemData['action_configuration'] ?? [],
                [
                    'add_notes' => !$item->getNotes(),
                    'edit_notes' => false,
                    'update_configurable' => false,
                ]
            );

            $data[] = $itemData;
        }
        unset($product);

        if (count($items) === 1) {
            foreach (reset($data) as $name => $value) {
                $record->setValue($name, $value);
            }

            if (!$lineItem->getProduct()->isConfigurable()) {
                $record->setValue('isConfigurable', false);
            }
            $record->setValue(
                'link',
                $this->urlGenerator->generate(
                    'oro_product_frontend_product_view',
                    ['id' => $parentProduct->getId(), 'variantProductId' => $lineItem->getProduct()->getId()]
                )
            );
        } else {
            $record->setValue('id', $parentProduct->getId() . '_' . $lineItem->getProductUnitCode());
            $record->setValue('sku', null);
            $record->setValue('subData', $data);
            $record->setValue('quantity', $rowQuantity);
            if ($rowSubtotal) {
                if ($rowDiscount) {
                    $record->setValue('discount', $this->numberFormatter->formatCurrency($rowDiscount, $currency));
                    $record
                        ->setValue('initial_subtotal', $this->numberFormatter->formatCurrency($rowSubtotal, $currency));
                    $rowSubtotal -= $rowDiscount;
                }

                $record->setValue('subtotal', $this->numberFormatter->formatCurrency($rowSubtotal, $currency));
            }

            $record->setValue('productId', $parentProduct->getId());
            $record->setValue('name', $this->localizationHelper->getLocalizedValue($parentProduct->getNames()));
            $record->setValue('unit', $lineItem->getProductUnitCode());
            $record->setValue(
                'link',
                $this->urlGenerator->generate('oro_product_frontend_product_view', ['id' => $parentProduct->getId()])
            );
            $record->setValue(
                'delete_link',
                $this->urlGenerator->generate(
                    'oro_api_shopping_list_frontend_delete_line_item_configurable',
                    ['productId' => $parentProduct->getId(), 'unitCode' => $lineItem->getProductUnitCode()]
                )
            );

            $image = $parentProduct->getImagesByType('listing')->first();
            if ($image) {
                $record->setValue(
                    'image',
                    $this->attachmentManager->getFilteredImageUrl($image->getImage(), 'product_small')
                );
            }
        }
    }

    /**
     * @param LineItem $item
     * @return array
     */
    private function getConfigurableProducts(LineItem $item): array
    {
        $configurableProductsVariantFields = $this->configurableProductProvider
            ->getVariantFieldsValuesForLineItem($item, true);

        return $configurableProductsVariantFields[$item->getProduct()->getId()] ?? [];
    }

    /**
     * @param array $errors
     * @param string $sku
     * @param string $unit
     * @return array
     */
    private function getErrors(array $errors, string $sku, string $unit): array
    {
        return array_map(
            static fn (ConstraintViolationInterface $error) => $error->getMessage(),
            $errors[sprintf('product.%s.%s', $sku, $unit)] ?? []
        );
    }

    /**
     * @param OrmResultAfter $event
     *
     * @return array
     */
    private function getLineItems(OrmResultAfter $event): array
    {
        $shoppingListId = $event->getDatagrid()
            ->getParameters()
            ->get('shopping_list_id');

        if (!$shoppingListId) {
            return [];
        }

        $repository = $event->getQuery()
            ->getEntityManager()
            ->getRepository(ShoppingList::class);

        $shoppingList = $repository->find($shoppingListId);
        if (!$shoppingList) {
            return [];
        }

        return $this->preloadLineItems($shoppingList->getLineItems(), $event->getRecords());
    }

    /**
     * @param Collection $lineItems
     * @param Record[] $records
     *
     * @return array
     */
    private function preloadLineItems(Collection $lineItems, array $records): array
    {
        if (!$records) {
            return [];
        }

        $lineItemsIds = array_merge(
            ...array_map(
                function (Record $record) {
                    return explode(',', $this->getRowId($record));
                },
                $records
            )
        );

        if (count($lineItemsIds) < $lineItems->count()) {
            $lineItemsIds = array_map('intval', $lineItemsIds);
            $criteria = Criteria::create();
            $criteria->andWhere(Criteria::expr()->in('id', $lineItemsIds));
            $lineItemsToPreload = $lineItems->matching($criteria)->toArray();
        } else {
            $lineItemsToPreload = $lineItems->toArray();
        }

        $this->preloadingManager->preloadInEntities($lineItemsToPreload, $this->fieldsToPreload);

        return $lineItemsToPreload;
    }

    /**
     * @param array $lineItems
     *
     * @return array
     */
    private function getIdentifiedLineItems(array $lineItems): array
    {
        $identifiedLineItems = [];
        foreach ($lineItems as $lineItem) {
            $identifiedLineItems[$lineItem->getId()] = $lineItem;
        }

        return $identifiedLineItems;
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @return array
     */
    private function getProductUnits(Product $product, ProductUnit $productUnit): array
    {
        $selectedCode = $productUnit->getCode();

        $data = [];
        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            if (!$unitPrecision->isSell()) {
                continue;
            }

            $unitCode = $unitPrecision->getUnit()->getCode();

            $data[$unitCode] = [
                'label' => $this->unitLabelFormatter->format($unitCode),
                'selected' => $unitCode === $selectedCode,
                'disabled' => false,
            ];
        }

        if (!isset($data[$selectedCode])) {
            $data[$selectedCode] = [
                'label' => $this->unitLabelFormatter->format($selectedCode),
                'selected' => true,
                'disabled' => true,
            ];
        }

        return $data;
    }
}
