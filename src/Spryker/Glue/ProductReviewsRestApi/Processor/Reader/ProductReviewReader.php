<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductReviewsRestApi\Processor\Reader;

use Generated\Shared\Transfer\BulkProductReviewSearchRequestTransfer;
use Generated\Shared\Transfer\ProductReviewSearchRequestTransfer;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\Page;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\GlueApplication\Rest\RequestConstantsInterface;
use Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductReviewClientInterface;
use Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductStorageClientInterface;
use Spryker\Glue\ProductReviewsRestApi\Processor\RestResponseBuilder\ProductReviewRestResponseBuilderInterface;
use Spryker\Glue\ProductReviewsRestApi\ProductReviewsRestApiConfig;

class ProductReviewReader implements ProductReviewReaderInterface
{
    protected const PRODUCT_ABSTRACT_MAPPING_TYPE = 'sku';

    protected const KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    /**
     * @uses \Spryker\Client\ProductReview\Plugin\Elasticsearch\ResultFormatter\ProductReviewsResultFormatterPlugin::NAME
     */
    protected const PRODUCT_REVIEWS = 'productReviews';

    /**
     * @uses \Spryker\Client\ProductReview\Plugin\Elasticsearch\ResultFormatter\PaginatedProductReviewsResultFormatterPlugin::NAME
     */
    protected const PAGINATION = 'pagination';

    /**
     * @var \Spryker\Glue\ProductReviewsRestApi\Processor\RestResponseBuilder\ProductReviewRestResponseBuilderInterface
     */
    protected $productReviewRestResponseBuilder;

    /**
     * @var \Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductStorageClientInterface
     */
    protected $productStorageClient;

    /**
     * @var \Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductReviewClientInterface
     */
    protected $productReviewClient;

    /**
     * @var \Spryker\Glue\ProductReviewsRestApi\ProductReviewsRestApiConfig
     */
    protected $productReviewsRestApiConfig;

    /**
     * @param \Spryker\Glue\ProductReviewsRestApi\Processor\RestResponseBuilder\ProductReviewRestResponseBuilderInterface $productReviewRestResponseBuilder
     * @param \Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductStorageClientInterface $productStorageClient
     * @param \Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductReviewClientInterface $productReviewClient
     * @param \Spryker\Glue\ProductReviewsRestApi\ProductReviewsRestApiConfig $productReviewsRestApiConfig
     */
    public function __construct(
        ProductReviewRestResponseBuilderInterface $productReviewRestResponseBuilder,
        ProductReviewsRestApiToProductStorageClientInterface $productStorageClient,
        ProductReviewsRestApiToProductReviewClientInterface $productReviewClient,
        ProductReviewsRestApiConfig $productReviewsRestApiConfig
    ) {
        $this->productReviewRestResponseBuilder = $productReviewRestResponseBuilder;
        $this->productStorageClient = $productStorageClient;
        $this->productReviewClient = $productReviewClient;
        $this->productReviewsRestApiConfig = $productReviewsRestApiConfig;
    }
    
    /**
     * @param \Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface $restRequest
     *
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    public function getProductReviews(RestRequestInterface $restRequest): RestResponseInterface
    {
        if ($restRequest->getResource()->getId()) {
            return $this->productReviewRestResponseBuilder->createNotImplementedErrorResponse();
        }

        $parentResource = $restRequest->findParentResourceByType(ProductReviewsRestApiConfig::RESOURCE_ABSTRACT_PRODUCTS);
        if (!$parentResource || !$parentResource->getId()) {
            return $this->productReviewRestResponseBuilder->createProductAbstractSkuMissingErrorResponse();
        }

        $productAbstractData = $this->productStorageClient->findProductAbstractStorageDataByMapping(
            static::PRODUCT_ABSTRACT_MAPPING_TYPE,
            $parentResource->getId(),
            $restRequest->getMetadata()->getLocale()
        );

        if (!$productAbstractData) {
            return $this->productReviewRestResponseBuilder->createProductAbstractNotFoundErrorResponse();
        }

        $productReviews = $this->getProductReviewsInSearch($restRequest, $productAbstractData[static::KEY_ID_PRODUCT_ABSTRACT]);

        return $this->productReviewRestResponseBuilder->createProductReviewRestResponse(
            $productReviews[static::PAGINATION]->getNumFound(),
            $restRequest->getPage()->getLimit(),
            $productReviews[static::PRODUCT_REVIEWS]
        );
    }

    /**
     * @param array $requestParams
     * @param int[] $productAbstractIds
     *
     * @return array
     */
    public function getProductReviewsDataByProductAbstractIds(
        array $requestParams,
        array $productAbstractIds
    ): array {
        /** @var \Generated\Shared\Transfer\ProductReviewTransfer[] $productReviewTransfers */
        $productReviewTransfers = $this->getBulkProductReviewsInSearch(
            $requestParams,
            $productAbstractIds
        )[static::PRODUCT_REVIEWS];

        $indexedProductReviewsData = [];
        foreach ($productReviewTransfers as $productReviewTransfer) {
            $indexedProductReviewsData[$productReviewTransfer->getFkProductAbstract()][] = $productReviewTransfer;
        }

        return $this->productReviewRestResponseBuilder->prepareRestResourceCollection(
            $indexedProductReviewsData,
            $productAbstractIds
        );
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface $restRequest
     * @param int $idProductAbstract
     *
     * @return array
     */
    protected function getProductReviewsInSearch(
        RestRequestInterface $restRequest,
        int $idProductAbstract
    ): array {
        $requestParams = $this->createRequestParamsWithPaginationParameters($restRequest);

        $productReviews = $this->productReviewClient->findProductReviewsInSearch(
            (new ProductReviewSearchRequestTransfer())
                ->setRequestParams($requestParams)
                ->setIdProductAbstract($idProductAbstract)
        );

        return $productReviews;
    }

    /**
     * @param array $requestParams
     * @param int[] $productAbstractIds
     *
     * @return array
     */
    protected function getBulkProductReviewsInSearch(
        array $requestParams,
        array $productAbstractIds
    ): array {
        $productReviews = $this->productReviewClient->getBulkProductReviewsFromSearch(
            (new BulkProductReviewSearchRequestTransfer())
                ->setRequestParams($requestParams)
                ->setProductAbstractIds($productAbstractIds)
        );

        return $productReviews;
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface $restRequest
     *
     * @return array
     */
    protected function createRequestParamsWithPaginationParameters(RestRequestInterface $restRequest): array
    {
        $requestParams = [];
        if (!$restRequest->getPage()) {
            $restRequest->setPage(new Page(0, $this->productReviewsRestApiConfig->getDefaultReviewsPerPage()));
        }

        $page = $restRequest->getPage();

        $requestParams[RequestConstantsInterface::QUERY_OFFSET] = $page->getOffset() ?: 0;
        $requestParams[RequestConstantsInterface::QUERY_LIMIT] = $page->getLimit() ?: $this->productReviewsRestApiConfig->getDefaultReviewsPerPage();

        return $requestParams;
    }
}
