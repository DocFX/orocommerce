<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\Api\ApiResponseContentTrait;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractRequestApiTest extends RestJsonApiTestCase
{
    use ApiResponseContentTrait;

    /**
     * @return string
     */
    abstract protected function getEntityClass();

    /**
     * @return array
     */
    abstract public function cgetParamsAndExpectation();

    /**
     * @param array $filters
     * @param int $expectedCount
     * @param array $params
     * @param array $expectedContent
     *
     * @dataProvider cgetParamsAndExpectation
     */
    public function testCgetEntity(array $filters, $expectedCount, array $params = [], array $expectedContent = null)
    {
        $entityType = $this->getEntityType($this->getEntityClass());

        foreach ($filters as $filter) {
            $filterValue = '';
            foreach ($filter['references'] as $value) {
                $method = $filter['method'];
                $filterValue .= $this->getReference($value)->$method() . $this->getArrayDelimiter();
            }
            $params['filter'][$filter['key']] = substr($filterValue, 0, -1);
        }

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityType]),
            $params
        );

        $this->assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');
        $content = json_decode($response->getContent(), true);
        $this->assertCount($expectedCount, $content['data']);

        if ($expectedContent) {
            $expectedContent = $this->addReferenceRelationshipsAndAssertIncluded(
                $expectedContent,
                $content['included']
            );
            $this->assertIsContained($expectedContent, $content['data']);
        }
    }

    /**
     * @return string
     */
    protected function getArrayDelimiter()
    {
        return ',';
    }

    /**
     * @param array $expectedContent
     * @param array $includedItems
     *
     * @return array
     */
    protected function addReferenceRelationshipsAndAssertIncluded(array $expectedContent, array $includedItems)
    {
        foreach ($expectedContent as $key => $expected) {
            if (array_key_exists('relationships', $expected)) {
                $expectedContent[$key]['relationships'] = $this->buildRelationships($expected, $includedItems);
            }
        }

        return $expectedContent;
    }

    /**
     * @param array $expected
     * @param array $includedItems
     *
     * @return array
     */
    protected function buildRelationships(array $expected, array $includedItems)
    {
        $relationships = [];
        foreach ($expected['relationships'] as $relationshipKey => $relationship) {
            if (array_key_exists('references', $relationship)) {
                foreach ($relationship['references'] as $reference) {
                    $method = $reference['method'];
                    $referenceId = $reference['reference'];
                    $relationship['data'][$reference['key']] = $this->getReference($referenceId)->$method();
                }
                unset($relationship['references']);
            }
            $relationships[$relationshipKey] = $relationship;
        }

        foreach ($relationships as $relationshipKey => $relationship) {
            if (array_key_exists('included', $relationship)) {
                foreach ($includedItems as $included) {
                    if ($included['type'] == $relationship['data']['type']
                        && $included['id'] == $relationship['data']['id']
                    ) {
                        $this->assertIsContained($relationship['included'], $included);
                    }
                }
                unset($relationships[$relationshipKey]['included']);
            }
        }

        return $relationships;
    }
}
