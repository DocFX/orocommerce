<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;

class LoadCombinedPriceListsActivationRules extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'fullCombinedPriceList' => '2f_1t_3t',
            'combinedPriceList' => '2f',
            'activateAtOffset' => '+12 hours',
            'expiredAtOffset' => '+24 hours',
            'active' => true
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $priceLisRuleData) {
            $combinedPriceListRule = new CombinedPriceListActivationRule();

            /** @var CombinedPriceList $fullCombinedPriceList */
            $fullCombinedPriceList = $this->getReference($priceLisRuleData['fullCombinedPriceList']);
            $combinedPriceListRule->setFullChainPriceList($fullCombinedPriceList);

            /** @var CombinedPriceList $combinedPriceList */
            $combinedPriceList = $this->getReference($priceLisRuleData['combinedPriceList']);
            $combinedPriceListRule->setCombinedPriceList($combinedPriceList);

            $combinedPriceListRule->setActive($priceLisRuleData['active']);
            $combinedPriceListRule->setActivateAt((new \DateTime('now', new \DateTimeZone("UTC")))
                ->modify($priceLisRuleData['activateAtOffset']));
            $combinedPriceListRule->setExpireAt((new \DateTime('now', new \DateTimeZone("UTC")))
                ->modify($priceLisRuleData['expiredAtOffset']));

            $manager->persist($combinedPriceListRule);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists'
        ];
    }
}
