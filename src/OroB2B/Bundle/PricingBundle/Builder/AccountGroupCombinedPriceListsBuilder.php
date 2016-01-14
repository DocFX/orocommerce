<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupCombinedPriceListsBuilder
{
    /**
     * @var PriceListCollectionProvider
     */
    protected $priceListCollectionProvider;

    /**
     * @var CombinedPriceListProvider
     */
    protected $combinedPriceListProvider;

    /**
     * @var string
     */
    protected $priceListToAccountGroupClassName;

    /**
     * @var string
     */
    protected $combinedPriceListToAccountGroupClassName;


    /**
     * @var PriceListToAccountGroupRepository
     */
    protected $combinedPriceListToAccountGroupRepository;

    /**
     * @var PriceListToAccountGroupRepository
     */
    protected $priceListToAccountGroupRepository;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var AccountCombinedPriceListsBuilder
     */
    protected $accountCombinedPriceListsBuilder;

    /**
     * @var CombinedPriceListGarbageCollector
     */
    protected $combinedPriceListGarbageCollector;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Website $website
     * @param AccountGroup|null $currentAccountGroup
     */
    public function build(Website $website, AccountGroup $currentAccountGroup = null)
    {
        $accountGroups = [$currentAccountGroup];
        if (!$currentAccountGroup) {
            $accountGroups = $this->getPriceListToAccountGroupRepository()
                ->getAccountGroupIteratorByFallback($website, PriceListAccountGroupFallback::WEBSITE);
        }

        foreach ($accountGroups as $accountGroup) {
            $this->updatePriceListsOnCurrentLevel($website, $accountGroup);
            $this->updatePriceListsOnChildrenLevels($website, $accountGroup);
        }

        if ($currentAccountGroup) {
            $this->combinedPriceListGarbageCollector->cleanCombinedPriceLists();
        }
    }

    /**
     * @param CombinedPriceListProvider $combinedPriceListProvider
     */
    public function setCombinedPriceListProvider($combinedPriceListProvider)
    {
        $this->combinedPriceListProvider = $combinedPriceListProvider;
    }

    /**
     * @param PriceListCollectionProvider $priceListCollectionProvider
     */
    public function setPriceListCollectionProvider($priceListCollectionProvider)
    {
        $this->priceListCollectionProvider = $priceListCollectionProvider;
    }

    /**
     * @param mixed $priceListToAccountGroupClassName
     */
    public function setCombinedPriceListToAccountGroupClassName($priceListToAccountGroupClassName)
    {
        $this->combinedPriceListToAccountGroupClassName = $priceListToAccountGroupClassName;
        $this->combinedPriceListToAccountGroupRepository = null;
    }

    /**
     * @param mixed $priceListToAccountGroupClassName
     */
    public function setPriceListToAccountGroupClassName($priceListToAccountGroupClassName)
    {
        $this->priceListToAccountGroupClassName = $priceListToAccountGroupClassName;
        $this->priceListToAccountGroupRepository = null;
    }

    /**
     * @param AccountCombinedPriceListsBuilder $accountCombinedPriceListsBuilder
     */
    public function setAccountCombinedPriceListsBuilder($accountCombinedPriceListsBuilder)
    {
        $this->accountCombinedPriceListsBuilder = $accountCombinedPriceListsBuilder;
    }

    /**
     * @param CombinedPriceListGarbageCollector $CPLGarbageCollector
     */
    public function setCombinedPriceListGarbageCollector(CombinedPriceListGarbageCollector $CPLGarbageCollector)
    {
        $this->combinedPriceListGarbageCollector = $CPLGarbageCollector;
    }

    /**
     * @param Website $website
     * @param AccountGroup $accountGroup
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, AccountGroup $accountGroup)
    {
        $collection = $this->priceListCollectionProvider->getPriceListsByAccountGroup($accountGroup, $website);
        $actualCombinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection);

        $relation = $this->getCombinedPriceListToAccountGroupRepository()
            ->findByPrimaryKey($actualCombinedPriceList, $accountGroup, $website);

        if (!$relation) {
            $this->connectNewPriceList($accountGroup, $actualCombinedPriceList);
        }
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     */
    protected function updatePriceListsOnChildrenLevels(Website $website, AccountGroup $accountGroup)
    {
        $this->accountCombinedPriceListsBuilder->buildByAccountGroup($website, $accountGroup);
    }

    /**
     * @param AccountGroup $accountGroup
     * @param CombinedPriceList $combinedPriceList
     */
    protected function connectNewPriceList(AccountGroup $accountGroup, CombinedPriceList $combinedPriceList)
    {
        $relation = $this->getCombinedPriceListToAccountGroupRepository()->findOneBy(['accountGroup' => $accountGroup]);
        $manager = $this->registry->getManagerForClass($this->combinedPriceListToAccountGroupClassName);
        if (!$relation) {
            $relation = new CombinedPriceListToAccountGroup();
            $relation->setPriceList($combinedPriceList);
            $relation->setAccountGroup($accountGroup);
            $manager->persist($relation);
        }
        $relation->setPriceList($combinedPriceList);
        $manager->flush();
    }

    /**
     * @return PriceListToAccountGroupRepository
     */
    protected function getPriceListToAccountGroupRepository()
    {
        if (!$this->priceListToAccountGroupRepository) {
            $class = $this->priceListToAccountGroupClassName;
            $this->priceListToAccountGroupRepository = $this->registry->getManagerForClass($class)
                ->getRepository($class);
        }

        return $this->priceListToAccountGroupRepository;
    }

    /**
     * @return PriceListToAccountGroupRepository
     */
    protected function getCombinedPriceListToAccountGroupRepository()
    {
        if (!$this->combinedPriceListToAccountGroupRepository) {
            $class = $this->combinedPriceListToAccountGroupClassName;
            $this->combinedPriceListToAccountGroupRepository = $this->registry->getManagerForClass($class)
                ->getRepository($class);
        }

        return $this->combinedPriceListToAccountGroupRepository;
    }
}
