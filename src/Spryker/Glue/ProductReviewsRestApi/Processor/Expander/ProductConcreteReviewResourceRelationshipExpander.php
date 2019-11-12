<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductReviewsRestApi\Processor\Expander;

use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductStorageClientInterface;
use Spryker\Glue\ProductReviewsRestApi\Processor\Reader\ProductReviewReaderInterface;
use Spryker\Glue\ProductReviewsRestApi\ProductReviewsRestApiConfig;

class ProductConcreteReviewResourceRelationshipExpander implements ProductConcreteReviewResourceRelationshipExpanderInterface
{
    protected const KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const KEY_SKU = 'sku';

    protected const PRODUCT_MAPPING_TYPE = 'sku';

    /**
     * @var \Spryker\Glue\ProductReviewsRestApi\Processor\Reader\ProductReviewReaderInterface
     */
    protected $productReviewReader;

    /**
     * @var \Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductStorageClientInterface
     */
    protected $productStorageClient;

    /**
     * @var \Spryker\Glue\ProductReviewsRestApi\ProductReviewsRestApiConfig
     */
    protected $productReviewsRestApiConfig;

    /**
     * @param \Spryker\Glue\ProductReviewsRestApi\Processor\Reader\ProductReviewReaderInterface $productReviewReader
     * @param \Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductStorageClientInterface $productStorageClient
     * @param \Spryker\Glue\ProductReviewsRestApi\ProductReviewsRestApiConfig $productReviewsRestApiConfig
     */
    public function __construct(
        ProductReviewReaderInterface $productReviewReader,
        ProductReviewsRestApiToProductStorageClientInterface $productStorageClient,
        ProductReviewsRestApiConfig $productReviewsRestApiConfig
    ) {
        $this->productReviewReader = $productReviewReader;
        $this->productStorageClient = $productStorageClient;
        $this->productReviewsRestApiConfig = $productReviewsRestApiConfig;
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface[] $resources
     * @param \Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface $restRequest
     *
     * @return void
     */
    public function addRelationshipsByConcreteSku(array $resources, RestRequestInterface $restRequest): void
    {
        $productConcreteSkus = $this->getAllSkus($resources);

        $productConcreteDataCollection = $this->productStorageClient->getProductConcreteStorageDataByMapping(
            static::PRODUCT_MAPPING_TYPE,
            $productConcreteSkus,
            $restRequest->getMetadata()->getLocale()
        );

        if (!$productConcreteDataCollection) {
            return;
        }

        $productAbstractIds = [];
        foreach ($productConcreteDataCollection as $productConcrete) {
            $productAbstractIds[] = $productConcrete[static::KEY_ID_PRODUCT_ABSTRACT];
        }

        $productReviewsRestResourcesCollection = $this->productReviewReader
            ->getProductReviewsResourceCollection(
                $productAbstractIds,
                $this->createRequestParams()
            );

        foreach ($resources as $resource) {
            foreach ($productReviewsRestResourcesCollection as $idProductAbstract => $productReviewsRestResources) {
                $this->addProductReviewsRelationship($idProductAbstract, $productConcreteDataCollection, $resource, $productReviewsRestResources);
            }
        }
    }

    /**
     * @param int $idProductAbstract
     * @param array $productConcreteDataCollection
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface $resource
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface[] $productReviewsRestResources
     *
     * @return void
     */
    protected function addProductReviewsRelationship(
        int $idProductAbstract,
        array $productConcreteDataCollection,
        RestResourceInterface $resource,
        array $productReviewsRestResources
    ): void {
        foreach ($productConcreteDataCollection as $productConcrete) {
            if ($idProductAbstract !== $productConcrete[static::KEY_ID_PRODUCT_ABSTRACT]) {
                continue;
            }

            if ($resource->getId() !== $productConcrete[static::KEY_SKU]
                || $productConcrete[static::KEY_ID_PRODUCT_ABSTRACT] !== $idProductAbstract
            ) {
                continue;
            }

            $this->addResourceRelationship($resource, $productReviewsRestResources);
        }
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface[] $resources
     *
     * @return string[]
     */
    protected function getAllSkus(array $resources): array
    {
        $skus = [];
        foreach ($resources as $resource) {
            $skus[] = $resource->getId();
        }

        return $skus;
    }

    /**
     * (
     *
     * @return array
     */
    protected function createRequestParams(): array
    {
        return [
            'offset' => 0,
            'limit' => $this->productReviewsRestApiConfig->getMaximumNumberOfResults(),
        ];
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface $resource
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface[] $productReviewsRestResources
     *
     * @return void
     */
    protected function addResourceRelationship(RestResourceInterface $resource, array $productReviewsRestResources): void
    {
        foreach ($productReviewsRestResources as $productReviewsRestResource) {
            $resource->addRelationship($productReviewsRestResource);
        }
    }
}
