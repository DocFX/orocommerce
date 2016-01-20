<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Model\Price;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

class LoadOrderItems extends AbstractFixture implements DependentFixtureInterface
{
    const ORDER_ITEM_1 = 'simple_order_item_1';
    const ORDER_ITEM_2 = 'simple_order_item_2';

    /**
     * @var array
     */
    protected $orderLineItems = [
        self::ORDER_ITEM_1 => [
            'quantity' => 5,
            'price' => '15.99',
        ],
        self::ORDER_ITEM_2 => [
            'quantity' => 6,
            'price' => '5.55',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $billingAddress = new OrderAddress();
        $billingAddress->setCountry(
            $manager->getRepository('OroAddressBundle:Country')->find(LoadTaxJurisdictions::COUNTRY_US)
        );
        $billingAddress->setRegion(
            $manager->getRepository('OroAddressBundle:Region')->find(LoadTaxJurisdictions::STATE_US_NY)
        );
        $order->setBillingAddress($billingAddress);

        foreach ($this->orderLineItems as $name => $orderLineItem) {
            $this->setReference(
                $name,
                $this->createOrderLineItem($manager, $order, $orderLineItem)
            );
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param Order $order
     * @param array $orderLineItemData
     * @return OrderLineItem
     */
    protected function createOrderLineItem(ObjectManager $manager, Order $order, array $orderLineItemData)
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem
            ->setQuantity($orderLineItemData['quantity'])
            ->setPrice(Price::create($orderLineItemData['price'], 'USD'));
        $order->addLineItem($orderLineItem);

        $manager->persist($order);

        return $orderLineItem;
    }
}
