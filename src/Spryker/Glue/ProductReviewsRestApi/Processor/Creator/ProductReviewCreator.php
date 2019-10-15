<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductReviewsRestApi\Processor\Creator;

use Generated\Shared\Transfer\ProductReviewRequestTransfer;
use Generated\Shared\Transfer\RestErrorMessageTransfer;
use Generated\Shared\Transfer\RestProductReviewsAttributesTransfer;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestLinkInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductReviewClientInterface;
use Spryker\Glue\ProductReviewsRestApi\ProductReviewsRestApiConfig;
use Spryker\Glue\ProductsRestApi\ProductsRestApiConfig;
use Symfony\Component\HttpFoundation\Response;

class ProductReviewCreator implements ProductReviewCreatorInterface
{
    protected const FORMAT_SELF_LINK_PRODUCT_REVIEWS_RESOURCE = '%s/%s/%s';

    /**
     * @var \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface
     */
    protected $restResourceBuilder;

    /**
     * @var \Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductReviewClientInterface
     */
    protected $productReviewClient;

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface $restResourceBuilder
     * @param \Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductReviewClientInterface $productReviewClient
     */
    public function __construct(
        RestResourceBuilderInterface $restResourceBuilder,
        ProductReviewsRestApiToProductReviewClientInterface $productReviewClient
    ) {
        $this->restResourceBuilder = $restResourceBuilder;
        $this->productReviewClient = $productReviewClient;
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface $restRequest
     * @param \Generated\Shared\Transfer\RestProductReviewsAttributesTransfer $restProductReviewAttributesTransfer
     *
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    public function createProductReview(
        RestRequestInterface $restRequest,
        RestProductReviewsAttributesTransfer $restProductReviewAttributesTransfer
    ): RestResponseInterface {
        $restResponse = $this->restResourceBuilder->createRestResponse();

        $parentResource = $restRequest->findParentResourceByType(ProductsRestApiConfig::RESOURCE_ABSTRACT_PRODUCTS);
        if (!$parentResource || !$parentResource->getId()) {
            return $this->createProductAbstractSkuMissingError();
        }

        $productReviewResponseTransfer = $this->productReviewClient->submitCustomerReview(
            (new ProductReviewRequestTransfer())->fromArray($restProductReviewAttributesTransfer->toArray())
                ->setIdProductAbstract($parentResource->getId())
                ->setLocaleName($restRequest->getMetadata()->getLocale())
                ->setCustomerReference($restRequest->getRestUser()->getNaturalIdentifier())
        );

        if (!$productReviewResponseTransfer->getIsSuccess()) {
            return $restResponse->addError(
                (new RestErrorMessageTransfer())
                    ->setStatus(Response::HTTP_FORBIDDEN)
            );
        }

        $restResource = $this->restResourceBuilder->createRestResource(
            ProductReviewsRestApiConfig::RESOURCE_PRODUCT_REVIEWS,
            $productReviewResponseTransfer->getProductReview()->getIdProductReview(),
            $restProductReviewAttributesTransfer
        );

        return $restResponse
            ->addResource($restResource->addLink(RestLinkInterface::LINK_SELF, $this->createSelfLink($parentResource->getId())))
            ->setStatus(Response::HTTP_ACCEPTED);
    }

    /**
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    protected function createProductAbstractSkuMissingError(): RestResponseInterface
    {
        $restErrorTransfer = (new RestErrorMessageTransfer())
            ->setCode(ProductsRestApiConfig::RESPONSE_CODE_ABSTRACT_PRODUCT_SKU_IS_NOT_SPECIFIED)
            ->setStatus(Response::HTTP_BAD_REQUEST)
            ->setDetail(ProductsRestApiConfig::RESPONSE_DETAIL_ABSTRACT_PRODUCT_SKU_IS_NOT_SPECIFIED);

        return $this->restResourceBuilder->createRestResponse()->addError($restErrorTransfer);
    }

    /**
     * @param string $abstractSku
     *
     * @return string
     */
    protected function createSelfLink(string $abstractSku): string
    {
        return sprintf(
            static::FORMAT_SELF_LINK_PRODUCT_REVIEWS_RESOURCE,
            ProductsRestApiConfig::RESOURCE_ABSTRACT_PRODUCTS,
            $abstractSku,
            ProductReviewsRestApiConfig::RESOURCE_PRODUCT_REVIEWS
        );
    }
}