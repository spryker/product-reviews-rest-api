<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductReviewsRestApi\Api\Storefront\Provider;

use Generated\Api\Storefront\ProductReviewsStorefrontResource;
use Generated\Shared\Transfer\ProductReviewSearchRequestTransfer;
use Generated\Shared\Transfer\ProductReviewTransfer;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Client\ProductReview\ProductReviewClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Glue\ProductReviewsRestApi\Api\Storefront\Exception\ProductReviewsExceptionFactory;

class ProductReviewsStorefrontProvider extends AbstractStorefrontProvider
{
    protected const string MAPPING_TYPE_SKU = 'sku';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string URI_VAR_ABSTRACT_SKU = 'abstractProductSku';

    protected const string URI_VAR_CONCRETE_SKU = 'concreteProductSku';

    protected const string URI_VAR_ID_PRODUCT_REVIEW = 'idProductReview';

    protected const string PRODUCT_REVIEWS_KEY = 'productReviews';

    /**
     * @uses \Spryker\Client\ProductReview\Plugin\Elasticsearch\ResultFormatter\PaginatedProductReviewsResultFormatterPlugin::NAME
     */
    protected const string PAGINATION_KEY = 'pagination';

    /**
     * @uses \Spryker\Client\ProductReview\Plugin\Elasticsearch\QueryExpander\FilterByReviewIdQueryExpanderPlugin::REQUEST_PARAM_ID_PRODUCT_REVIEW
     */
    protected const string REQUEST_PARAM_ID_PRODUCT_REVIEW = 'idProductReview';

    protected const string CONCRETE_PRODUCTS_PATH_PREFIX = '/concrete-products/';

    protected const int DEFAULT_REVIEWS_PER_PAGE = 10;

    public function __construct(
        protected ProductStorageClientInterface $productStorageClient,
        protected ProductReviewClientInterface $productReviewClient,
        protected ProductReviewsExceptionFactory $exceptionFactory,
    ) {
    }

    /**
     * @return array<\Generated\Api\Storefront\ProductReviewsStorefrontResource>
     */
    protected function provideCollection(): array
    {
        $idProductAbstract = $this->resolveIdProductAbstract();

        if ($idProductAbstract === null) {
            return [];
        }

        $limit = $this->getPaginationLimit(static::DEFAULT_REVIEWS_PER_PAGE);
        $offset = $this->getPaginationOffset();

        $result = $this->productReviewClient->findProductReviewsInSearch(
            (new ProductReviewSearchRequestTransfer())
                ->setIdProductAbstract($idProductAbstract)
                ->setRequestParams([
                    static::QUERY_PARAMETER_OFFSET => $offset,
                    static::QUERY_PARAMETER_LIMIT => $limit,
                ]),
        );

        $reviews = $result[static::PRODUCT_REVIEWS_KEY] ?? [];
        $abstractProductSku = (string)($this->getUriVariables()[static::URI_VAR_ABSTRACT_SKU] ?? '');
        $resources = [];

        foreach ($reviews as $review) {
            if (!$review instanceof ProductReviewTransfer) {
                continue;
            }

            $resources[] = $this->mapReviewToResource($review, $abstractProductSku);
        }

        if ($resources !== []) {
            $totalCount = (int)($result[static::PAGINATION_KEY]?->getNumFound() ?? count($resources));
            // Consumed by Spryker\ApiPlatform\EventSubscriber\PaginationLinksResponseSubscriber
            // to emit JSON:API top-level pagination links (first/last/prev/next).
            $resources[0]->pagination = $this->calculatePagination($offset, $limit, $totalCount);
        }

        return $resources;
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     *
     * @return \Generated\Api\Storefront\ProductReviewsStorefrontResource|null
     */
    protected function provideItem(): ?object
    {
        $idProductAbstract = $this->resolveIdProductAbstract();

        if ($idProductAbstract === null) {
            throw $this->exceptionFactory->createProductReviewNotFoundException();
        }

        $idProductReview = (string)($this->getUriVariables()[static::URI_VAR_ID_PRODUCT_REVIEW] ?? '');

        if ($idProductReview === '') {
            throw $this->exceptionFactory->createProductReviewNotFoundException();
        }

        $result = $this->productReviewClient->findProductReviewsInSearch(
            (new ProductReviewSearchRequestTransfer())
                ->setIdProductAbstract($idProductAbstract)
                ->setRequestParams([static::REQUEST_PARAM_ID_PRODUCT_REVIEW => $idProductReview]),
        );

        $review = $this->findReviewById($result[static::PRODUCT_REVIEWS_KEY] ?? [], $idProductReview);

        if ($review === null) {
            throw $this->exceptionFactory->createProductReviewNotFoundException();
        }

        return $this->mapReviewToResource(
            $review,
            (string)($this->getUriVariables()[static::URI_VAR_ABSTRACT_SKU] ?? ''),
        );
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function resolveIdProductAbstract(): ?int
    {
        $localeName = $this->getLocale()->getLocaleNameOrFail();

        if ($this->isConcreteProductPath()) {
            // Concrete-products path is reached only via `?include=product-reviews` from
            // ConcreteProducts (no standalone /concrete-products/{sku}/product-reviews route).
            // ConcreteProducts always provides a non-empty `sku`, so empty here is unexpected
            // and is treated defensively as "no reviews".
            $sku = (string)($this->getUriVariables()[static::URI_VAR_CONCRETE_SKU] ?? '');

            if ($sku === '') {
                return null;
            }

            $data = $this->productStorageClient->findProductConcreteStorageDataByMapping(
                static::MAPPING_TYPE_SKU,
                $sku,
                $localeName,
            );

            if ($data === null) {
                return null;
            }

            return (int)($data[static::KEY_ID_PRODUCT_ABSTRACT] ?? 0) ?: null;
        }

        $sku = (string)($this->getUriVariables()[static::URI_VAR_ABSTRACT_SKU] ?? '');

        if ($sku === '') {
            throw $this->exceptionFactory->createMissingAbstractProductSkuException();
        }

        $data = $this->productStorageClient->findProductAbstractStorageDataByMapping(
            static::MAPPING_TYPE_SKU,
            $sku,
            $localeName,
        );

        if ($data === null) {
            throw $this->exceptionFactory->createAbstractProductNotFoundException();
        }

        return (int)($data[static::KEY_ID_PRODUCT_ABSTRACT] ?? 0) ?: null;
    }

    protected function isConcreteProductPath(): bool
    {
        return str_starts_with($this->getRequest()->getPathInfo(), static::CONCRETE_PRODUCTS_PATH_PREFIX);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\ProductReviewTransfer> $reviews
     */
    protected function findReviewById(array $reviews, string $idProductReview): ?ProductReviewTransfer
    {
        foreach ($reviews as $review) {
            if ((string)$review->getIdProductReview() === $idProductReview) {
                return $review;
            }
        }

        return null;
    }

    protected function mapReviewToResource(ProductReviewTransfer $review, string $abstractProductSku): ProductReviewsStorefrontResource
    {
        $resource = new ProductReviewsStorefrontResource();
        $resource->idProductReview = (string)$review->getIdProductReview();
        $resource->abstractProductSku = $abstractProductSku;
        $resource->rating = $review->getRating();
        $resource->nickname = $review->getNickname();
        $resource->summary = $review->getSummary();
        $resource->description = $review->getDescription();

        return $resource;
    }
}
