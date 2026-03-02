<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductReviewsRestApi\Dependency\Client;

use Generated\Shared\Transfer\StoreTransfer;

interface ProductReviewsRestApiToStoreClientInterface
{
    public function getCurrentStore(): StoreTransfer;
}
