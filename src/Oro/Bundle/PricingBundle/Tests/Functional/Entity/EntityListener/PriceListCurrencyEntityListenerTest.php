<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PriceListCurrencyEntityListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadPriceRules::class]);
        $this->enableMessageBuffering();
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(ProductPrice::class);
    }

    public function testPostPersist()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList->addCurrencyByCode('UAH');
        $this->getEntityManager()->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                'product' => [$priceList->getId() => []]
            ]
        );

        self::assertMessageSent(
            Topics::RESOLVE_COMBINED_CURRENCIES,
            [
                'product' => [$priceList->getId() => []]
            ]
        );
    }

    public function testPreRemove()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList->removeCurrencyByCode('USD');
        $this->getEntityManager()->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                'product' => [$priceList->getId() => []]
            ]
        );

        self::assertMessageSent(
            Topics::RESOLVE_COMBINED_CURRENCIES,
            [
                'product' => [$priceList->getId() => []]
            ]
        );
    }
}
