<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductReviewsRestApi\Api\Storefront\Exception;

use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\Glue\ProductReviewsRestApi\ProductReviewsRestApiConfig;
use Symfony\Component\HttpFoundation\Response;

class ProductReviewsExceptionFactory
{
    public function createMissingAbstractProductSkuException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            ProductReviewsRestApiConfig::RESPONSE_CODE_ABSTRACT_PRODUCT_SKU_IS_NOT_SPECIFIED,
            ProductReviewsRestApiConfig::RESPONSE_DETAIL_ABSTRACT_PRODUCT_SKU_IS_NOT_SPECIFIED,
        );
    }

    public function createAbstractProductNotFoundException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_NOT_FOUND,
            ProductReviewsRestApiConfig::RESPONSE_CODE_CANT_FIND_ABSTRACT_PRODUCT,
            ProductReviewsRestApiConfig::RESPONSE_DETAIL_CANT_FIND_ABSTRACT_PRODUCT,
        );
    }

    public function createProductReviewNotFoundException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_NOT_FOUND,
            ProductReviewsRestApiConfig::RESPONSE_CODE_CANT_FIND_PRODUCT_REVIEW,
            ProductReviewsRestApiConfig::RESPONSE_DETAIL_CANT_FIND_PRODUCT_REVIEW,
        );
    }
}
