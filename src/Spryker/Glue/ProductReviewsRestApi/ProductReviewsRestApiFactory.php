<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductReviewsRestApi;

use Spryker\Glue\Kernel\AbstractFactory;
use Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductReviewClientInterface;
use Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductReviewStorageClientInterface;
use Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToProductStorageClientInterface;
use Spryker\Glue\ProductReviewsRestApi\Dependency\Client\ProductReviewsRestApiToStoreClientInterface;
use Spryker\Glue\ProductReviewsRestApi\Processor\Creator\ProductReviewCreator;
use Spryker\Glue\ProductReviewsRestApi\Processor\Creator\ProductReviewCreatorInterface;
use Spryker\Glue\ProductReviewsRestApi\Processor\Expander\ProductAbstractReviewResourceRelationshipExpander;
use Spryker\Glue\ProductReviewsRestApi\Processor\Expander\ProductAbstractReviewResourceRelationshipExpanderInterface;
use Spryker\Glue\ProductReviewsRestApi\Processor\Expander\ProductConcreteReviewResourceRelationshipExpander;
use Spryker\Glue\ProductReviewsRestApi\Processor\Expander\ProductConcreteReviewResourceRelationshipExpanderInterface;
use Spryker\Glue\ProductReviewsRestApi\Processor\Expander\ProductReviewsAbstractProductsResourceExpander;
use Spryker\Glue\ProductReviewsRestApi\Processor\Expander\ProductReviewsAbstractProductsResourceExpanderInterface;
use Spryker\Glue\ProductReviewsRestApi\Processor\Expander\ProductReviewsConcreteProductsResourceExpander;
use Spryker\Glue\ProductReviewsRestApi\Processor\Expander\ProductReviewsConcreteProductsResourceExpanderInterface;
use Spryker\Glue\ProductReviewsRestApi\Processor\Mapper\ProductReviewMapper;
use Spryker\Glue\ProductReviewsRestApi\Processor\Mapper\ProductReviewMapperInterface;
use Spryker\Glue\ProductReviewsRestApi\Processor\Reader\ProductReviewReader;
use Spryker\Glue\ProductReviewsRestApi\Processor\Reader\ProductReviewReaderInterface;
use Spryker\Glue\ProductReviewsRestApi\Processor\RestResponseBuilder\ProductReviewRestResponseBuilder;
use Spryker\Glue\ProductReviewsRestApi\Processor\RestResponseBuilder\ProductReviewRestResponseBuilderInterface;

/**
 * @method \Spryker\Glue\ProductReviewsRestApi\ProductReviewsRestApiConfig getConfig()
 */
class ProductReviewsRestApiFactory extends AbstractFactory
{
    public function createProductReviewReader(): ProductReviewReaderInterface
    {
        return new ProductReviewReader(
            $this->createProductReviewRestResponseBuilder(),
            $this->getProductStorageClient(),
            $this->getProductReviewClient(),
            $this->getConfig(),
        );
    }

    public function createProductReviewCreator(): ProductReviewCreatorInterface
    {
        return new ProductReviewCreator(
            $this->createProductReviewRestResponseBuilder(),
            $this->getProductReviewClient(),
            $this->getProductStorageClient(),
        );
    }

    public function createProductReviewMapper(): ProductReviewMapperInterface
    {
        return new ProductReviewMapper();
    }

    public function createProductReviewsConcreteProductsResourceExpander(): ProductReviewsConcreteProductsResourceExpanderInterface
    {
        return new ProductReviewsConcreteProductsResourceExpander(
            $this->getProductReviewStorageClient(),
            $this->getProductStorageClient(),
        );
    }

    public function createProductReviewsAbstractProductsResourceExpander(): ProductReviewsAbstractProductsResourceExpanderInterface
    {
        return new ProductReviewsAbstractProductsResourceExpander($this->getProductReviewStorageClient());
    }

    public function createProductAbstractReviewResourceRelationshipExpander(): ProductAbstractReviewResourceRelationshipExpanderInterface
    {
        return new ProductAbstractReviewResourceRelationshipExpander(
            $this->createProductReviewReader(),
            $this->getProductStorageClient(),
            $this->getConfig(),
        );
    }

    public function createProductConcreteReviewResourceRelationshipExpander(): ProductConcreteReviewResourceRelationshipExpanderInterface
    {
        return new ProductConcreteReviewResourceRelationshipExpander(
            $this->createProductReviewReader(),
            $this->getProductStorageClient(),
            $this->getStoreClient(),
            $this->getConfig(),
        );
    }

    public function createProductReviewRestResponseBuilder(): ProductReviewRestResponseBuilderInterface
    {
        return new ProductReviewRestResponseBuilder(
            $this->getResourceBuilder(),
            $this->createProductReviewMapper(),
        );
    }

    public function getProductReviewStorageClient(): ProductReviewsRestApiToProductReviewStorageClientInterface
    {
        return $this->getProvidedDependency(ProductReviewsRestApiDependencyProvider::CLIENT_PRODUCT_REVIEW_STORAGE);
    }

    public function getProductStorageClient(): ProductReviewsRestApiToProductStorageClientInterface
    {
        return $this->getProvidedDependency(ProductReviewsRestApiDependencyProvider::CLIENT_PRODUCT_STORAGE);
    }

    public function getStoreClient(): ProductReviewsRestApiToStoreClientInterface
    {
        return $this->getProvidedDependency(ProductReviewsRestApiDependencyProvider::CLIENT_STORE);
    }

    public function getProductReviewClient(): ProductReviewsRestApiToProductReviewClientInterface
    {
        return $this->getProvidedDependency(ProductReviewsRestApiDependencyProvider::CLIENT_PRODUCT_REVIEW);
    }
}
