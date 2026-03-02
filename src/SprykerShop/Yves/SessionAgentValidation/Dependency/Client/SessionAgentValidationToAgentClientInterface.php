<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\SessionAgentValidation\Dependency\Client;

use Generated\Shared\Transfer\UserTransfer;

interface SessionAgentValidationToAgentClientInterface
{
    public function findAgentByUsername(UserTransfer $userTransfer): ?UserTransfer;

    public function isLoggedIn(): bool;

    public function getAgent(): UserTransfer;
}
