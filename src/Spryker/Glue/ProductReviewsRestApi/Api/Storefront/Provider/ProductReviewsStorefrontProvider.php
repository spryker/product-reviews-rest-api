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

class ProductReviewsStorefrontProvider extends AbstractStorefrontProvider
{
    protected const string MAPPING_TYPE_SKU = 'sku';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string URI_VAR_ABSTRACT_SKU = 'abstractProductSku';

    protected const string URI_VAR_CONCRETE_SKU = 'concreteProductSku';

    protected const string PRODUCT_REVIEWS_KEY = 'productReviews';

    protected const int DEFAULT_REVIEWS_PER_PAGE = 10;

    public function __construct(
        protected ProductStorageClientInterface $productStorageClient,
        protected ProductReviewClientInterface $productReviewClient,
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

        $result = $this->productReviewClient->findProductReviewsInSearch(
            (new ProductReviewSearchRequestTransfer())
                ->setIdProductAbstract($idProductAbstract)
                ->setRequestParams(['page' => 1, 'ipp' => static::DEFAULT_REVIEWS_PER_PAGE]),
        );

        $reviews = $result[static::PRODUCT_REVIEWS_KEY] ?? [];
        $resources = [];

        foreach ($reviews as $review) {
            if (!$review instanceof ProductReviewTransfer) {
                continue;
            }

            $resources[] = $this->mapReviewToResource($review);
        }

        return $resources;
    }

    protected function resolveIdProductAbstract(): ?int
    {
        $uriVariables = $this->getUriVariables();
        $localeName = $this->getLocale()->getLocaleNameOrFail();

        if (isset($uriVariables[static::URI_VAR_ABSTRACT_SKU])) {
            $data = $this->productStorageClient->findProductAbstractStorageDataByMapping(
                static::MAPPING_TYPE_SKU,
                (string)$uriVariables[static::URI_VAR_ABSTRACT_SKU],
                $localeName,
            );

            if ($data === null) {
                return null;
            }

            return (int)($data[static::KEY_ID_PRODUCT_ABSTRACT] ?? 0) ?: null;
        }

        if (isset($uriVariables[static::URI_VAR_CONCRETE_SKU])) {
            $data = $this->productStorageClient->findProductConcreteStorageDataByMapping(
                static::MAPPING_TYPE_SKU,
                (string)$uriVariables[static::URI_VAR_CONCRETE_SKU],
                $localeName,
            );

            if ($data === null) {
                return null;
            }

            return (int)($data[static::KEY_ID_PRODUCT_ABSTRACT] ?? 0) ?: null;
        }

        return null;
    }

    protected function mapReviewToResource(ProductReviewTransfer $review): ProductReviewsStorefrontResource
    {
        $resource = new ProductReviewsStorefrontResource();
        $resource->idProductReview = (string)$review->getIdProductReview();
        $resource->rating = $review->getRating();
        $resource->nickname = $review->getNickname();
        $resource->summary = $review->getSummary();
        $resource->description = $review->getDescription();

        return $resource;
    }
}
