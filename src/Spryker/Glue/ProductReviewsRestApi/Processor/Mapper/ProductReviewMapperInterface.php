<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductReviewsRestApi\Processor\Mapper;

use Generated\Shared\Transfer\ProductReviewTransfer;
use Generated\Shared\Transfer\RestProductReviewsAttributesTransfer;

interface ProductReviewMapperInterface
{
    public function mapProductReviewTransferToRestProductReviewsAttributesTransfer(
        ProductReviewTransfer $productReviewTransfer,
        RestProductReviewsAttributesTransfer $restProductReviewsAttributesTransfer
    ): RestProductReviewsAttributesTransfer;
}
