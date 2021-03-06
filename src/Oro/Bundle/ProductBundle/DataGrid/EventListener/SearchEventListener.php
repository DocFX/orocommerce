<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

/**
 * Managed for product search and provides the opportunity to work with a subset of products in the product grid
 */
class SearchEventListener
{
    /** @var SearchProductHandler */
    private $searchProductHandler;

    /** @var ProductRepository */
    private $searchRepository;

    /**
     * @param SearchProductHandler $searchProductHandler
     * @param ProductRepository $searchRepository
     */
    public function __construct(
        SearchProductHandler $searchProductHandler,
        ProductRepository $searchRepository
    ) {
        $this->searchProductHandler = $searchProductHandler;
        $this->searchRepository = $searchRepository;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event): void
    {
        $searchString = $event->getParameters()->has(SearchProductHandler::SEARCH_KEY)
            ? $event->getParameters()->get(SearchProductHandler::SEARCH_KEY)
            : $this->searchProductHandler->getSearchString();


        if ($searchString) {
            $event->getConfig()->offsetSetByPath($this->getConfigPath(), $searchString);

            return;
        }
        $event->getConfig()->offsetUnsetByPath($this->getConfigPath());
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event): void
    {
        $dataSource = $event->getDatagrid()->getDatasource();
        $searchString = $event->getDatagrid()->getConfig()->offsetGetByPath($this->getConfigPath());

        if (!$dataSource instanceof SearchDatasource || !$searchString) {
            return;
        }

        $operator = $this->searchRepository->getProductSearchOperator();

        $query = $dataSource->getSearchQuery();
        $query->addWhere(Criteria::expr()->$operator('all_text_LOCALIZATION_ID', $searchString));
    }

    /**
     * @return string
     */
    private function getConfigPath(): string
    {
        return sprintf('[options][urlParams][%s]', SearchProductHandler::SEARCH_KEY);
    }
}
