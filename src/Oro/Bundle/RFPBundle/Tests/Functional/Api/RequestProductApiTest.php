<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api;

use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class RequestProductApiTest extends AbstractRequestApiTest
{
    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadRequestData::class]);
    }

    /**
     * @return string
     */
    protected function getEntityClass()
    {
        return RequestProduct::class;
    }

    /**
     * @return array
     */
    public function cgetParamsAndExpectation()
    {
        $maxCount = LoadRequestData::NUM_REQUESTS * LoadRequestData::NUM_LINE_ITEMS * LoadRequestData::NUM_PRODUCTS;

        return [
            [
                'filters' => [],
                'expectedCount' => $maxCount,
                'params' => [],
                'expectedContent' => null,
            ],
        ];
    }
}
