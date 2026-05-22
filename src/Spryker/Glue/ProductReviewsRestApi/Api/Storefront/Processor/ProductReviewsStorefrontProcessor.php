<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductReviewsRestApi\Api\Storefront\Processor;

use Generated\Api\Storefront\ProductReviewsStorefrontResource;
use Generated\Shared\Transfer\ProductReviewRequestTransfer;
use Generated\Shared\Transfer\ProductReviewTransfer;
use Spryker\ApiPlatform\State\Processor\AbstractStorefrontProcessor;
use Spryker\Client\ProductReview\ProductReviewClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Glue\ProductReviewsRestApi\Api\Storefront\Exception\ProductReviewsExceptionFactory;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ProductReviewsStorefrontProcessor extends AbstractStorefrontProcessor
{
    protected const string MAPPING_TYPE_SKU = 'sku';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string URI_VAR_ABSTRACT_SKU = 'abstractProductSku';

    public function __construct(
        protected ProductStorageClientInterface $productStorageClient,
        protected ProductReviewClientInterface $productReviewClient,
        protected ProductReviewsExceptionFactory $exceptionFactory,
    ) {
    }

    /**
     * @param \Generated\Api\Storefront\ProductReviewsStorefrontResource $data
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    protected function processPost(mixed $data): ProductReviewsStorefrontResource
    {
        $idProductAbstract = $this->resolveIdProductAbstract();

        $responseTransfer = $this->productReviewClient->submitCustomerReview(
            $this->buildRequestTransfer($data, $idProductAbstract),
        );

        if (!$responseTransfer->getIsSuccess()) {
            throw new UnprocessableEntityHttpException($this->collectErrorMessages($responseTransfer->getErrors()));
        }

        return $this->mapReviewToResource($responseTransfer->getProductReviewOrFail(), $data);
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function resolveIdProductAbstract(): int
    {
        $sku = (string)($this->getUriVariables()[static::URI_VAR_ABSTRACT_SKU] ?? '');

        if ($sku === '') {
            throw $this->exceptionFactory->createMissingAbstractProductSkuException();
        }

        $data = $this->productStorageClient->findProductAbstractStorageDataByMapping(
            static::MAPPING_TYPE_SKU,
            $sku,
            $this->getLocale()->getLocaleNameOrFail(),
        );

        if ($data === null) {
            throw $this->exceptionFactory->createAbstractProductNotFoundException();
        }

        return (int)$data[static::KEY_ID_PRODUCT_ABSTRACT];
    }

    protected function buildRequestTransfer(
        ProductReviewsStorefrontResource $data,
        int $idProductAbstract,
    ): ProductReviewRequestTransfer {
        return (new ProductReviewRequestTransfer())
            ->setIdProductAbstract($idProductAbstract)
            ->setRating($data->rating)
            ->setNickname($data->nickname)
            ->setSummary($data->summary)
            ->setDescription($data->description)
            ->setLocaleName($this->getLocale()->getLocaleNameOrFail())
            ->setCustomerReference($this->getCustomerReference());
    }

    protected function mapReviewToResource(
        ProductReviewTransfer $review,
        ProductReviewsStorefrontResource $data,
    ): ProductReviewsStorefrontResource {
        $data->idProductReview = (string)$review->getIdProductReview();
        $data->abstractProductSku = (string)($this->getUriVariables()[static::URI_VAR_ABSTRACT_SKU] ?? '');
        $data->rating = $review->getRating();
        $data->nickname = $review->getNickname();
        $data->summary = $review->getSummary();
        $data->description = $review->getDescription();

        return $data;
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\ProductReviewErrorTransfer> $errors
     */
    protected function collectErrorMessages(iterable $errors): string
    {
        $messages = [];
        foreach ($errors as $error) {
            if ($error->getMessage() !== null) {
                $messages[] = $error->getMessage();
            }
        }

        return implode('; ', $messages) ?: 'Product review submission failed.';
    }
}
