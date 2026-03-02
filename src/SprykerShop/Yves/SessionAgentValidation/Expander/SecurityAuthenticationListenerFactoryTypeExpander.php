<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\SessionAgentValidation\Expander;

use SprykerShop\Yves\SessionAgentValidation\SessionAgentValidationConfig;

class SecurityAuthenticationListenerFactoryTypeExpander implements SecurityAuthenticationListenerFactoryTypeExpanderInterface
{
    /**
     * @var \SprykerShop\Yves\SessionAgentValidation\SessionAgentValidationConfig
     */
    protected SessionAgentValidationConfig $config;

    public function __construct(SessionAgentValidationConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param array<string> $authenticationListenerFactoryTypes
     *
     * @return array<string>
     */
    public function expand(array $authenticationListenerFactoryTypes): array
    {
        $authenticationListenerFactoryTypes[] = $this->config->getAuthenticationListenerFactoryType();

        return $authenticationListenerFactoryTypes;
    }
}
