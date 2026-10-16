<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\SessionAgentValidation\Updater;

use Generated\Shared\Transfer\SessionEntityRequestTransfer;
use SprykerShop\Yves\SessionAgentValidation\Dependency\Client\SessionAgentValidationToAgentClientInterface;
use SprykerShop\Yves\SessionAgentValidation\Dependency\Client\SessionAgentValidationToSessionClientInterface;
use SprykerShop\Yves\SessionAgentValidation\SessionAgentValidationConfig;
use SprykerShop\Yves\SessionAgentValidationExtension\Dependency\Plugin\SessionAgentSaverPluginInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SessionAgentValidationSessionUpdater implements SessionAgentValidationSessionUpdaterInterface
{
    /**
     * @uses \SprykerShop\Yves\AgentPage\Plugin\Security\AgentPageSecurityPlugin::ROLE_PREVIOUS_ADMIN
     *
     * @var string
     */
    protected const ROLE_PREVIOUS_ADMIN = 'ROLE_PREVIOUS_ADMIN';

    protected AuthorizationCheckerInterface $authorizationChecker;

    protected SessionAgentValidationToAgentClientInterface $agentClient;

    protected SessionAgentValidationToSessionClientInterface $sessionClient;

    protected SessionAgentSaverPluginInterface $sessionAgentSaverPlugin;

    protected SessionAgentValidationConfig $config;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        SessionAgentValidationToAgentClientInterface $agentClient,
        SessionAgentValidationToSessionClientInterface $sessionClient,
        SessionAgentSaverPluginInterface $sessionAgentSaverPlugin,
        SessionAgentValidationConfig $config
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->agentClient = $agentClient;
        $this->sessionClient = $sessionClient;
        $this->sessionAgentSaverPlugin = $sessionAgentSaverPlugin;
        $this->config = $config;
    }

    public function update(): void
    {
        if (!$this->isSessionUpdatable()) {
            return;
        }

        $this->sessionAgentSaverPlugin->saveSession(
            (new SessionEntityRequestTransfer())
                ->setIdEntity($this->agentClient->getAgent()->getIdUserOrFail())
                ->setIdSession($this->sessionClient->getId())
                ->setEntityType($this->config->getSessionEntityType()),
        );
    }

    protected function isSessionUpdatable(): bool
    {
        return $this->authorizationChecker->isGranted(static::ROLE_PREVIOUS_ADMIN)
            && $this->agentClient->isLoggedIn();
    }
}
