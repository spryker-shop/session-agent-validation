<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\SessionAgentValidation;

use Spryker\Yves\Kernel\AbstractFactory;
use SprykerShop\Yves\SessionAgentValidation\Dependency\Client\SessionAgentValidationToAgentClientInterface;
use SprykerShop\Yves\SessionAgentValidation\Dependency\Client\SessionAgentValidationToSessionClientInterface;
use SprykerShop\Yves\SessionAgentValidation\EventSubscriber\SaveAgentSessionEventSubscriber;
use SprykerShop\Yves\SessionAgentValidation\Expander\SecurityAuthenticationListenerFactoryTypeExpander;
use SprykerShop\Yves\SessionAgentValidation\Expander\SecurityAuthenticationListenerFactoryTypeExpanderInterface;
use SprykerShop\Yves\SessionAgentValidation\Extender\SessionAgentValidationSecurityExtender;
use SprykerShop\Yves\SessionAgentValidation\Extender\SessionAgentValidationSecurityExtenderInterface;
use SprykerShop\Yves\SessionAgentValidation\FirewallListener\ValidateAgentSessionListener;
use SprykerShop\Yves\SessionAgentValidation\FirewallListener\ValidateAgentSessionListenerInterface;
use SprykerShop\Yves\SessionAgentValidation\Updater\SessionAgentValidationSessionUpdater;
use SprykerShop\Yves\SessionAgentValidation\Updater\SessionAgentValidationSessionUpdaterInterface;
use SprykerShop\Yves\SessionAgentValidationExtension\Dependency\Plugin\SessionAgentSaverPluginInterface;
use SprykerShop\Yves\SessionAgentValidationExtension\Dependency\Plugin\SessionAgentValidatorPluginInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

/**
 * @method \SprykerShop\Yves\SessionAgentValidation\SessionAgentValidationConfig getConfig()
 */
class SessionAgentValidationFactory extends AbstractFactory
{
    public function createSaveAgentSessionEventSubscriber(): EventSubscriberInterface
    {
        return new SaveAgentSessionEventSubscriber(
            $this->getAgentClient(),
            $this->getSessionAgentSaverPlugin(),
            $this->getConfig(),
            $this->createSessionAuthenticationStrategy(),
        );
    }

    public function createSessionAuthenticationStrategy(): SessionAuthenticationStrategyInterface
    {
        return new SessionAuthenticationStrategy(
            SessionAuthenticationStrategy::MIGRATE,
            $this->createCsrfTokenStorage(),
        );
    }

    public function createCsrfTokenStorage(): ClearableTokenStorageInterface
    {
        return new SessionTokenStorage($this->getRequestStack());
    }

    public function createValidateAgentSessionListener(): ValidateAgentSessionListenerInterface
    {
        return new ValidateAgentSessionListener(
            $this->getAgentClient(),
            $this->getSessionAgentValidatorPlugin(),
            $this->getConfig(),
        );
    }

    public function createSessionAgentValidationSecurityExtender(): SessionAgentValidationSecurityExtenderInterface
    {
        return new SessionAgentValidationSecurityExtender(
            $this->createValidateAgentSessionListener(),
            $this->getConfig(),
        );
    }

    public function createSessionAgentValidationSessionUpdater(): SessionAgentValidationSessionUpdaterInterface
    {
        return new SessionAgentValidationSessionUpdater(
            $this->getSecurityAuthorizationChecker(),
            $this->getAgentClient(),
            $this->getSessionClient(),
            $this->getSessionAgentSaverPlugin(),
            $this->getConfig(),
        );
    }

    public function createSecurityAuthenticationListenerFactoryTypeExpander(): SecurityAuthenticationListenerFactoryTypeExpanderInterface
    {
        return new SecurityAuthenticationListenerFactoryTypeExpander($this->getConfig());
    }

    public function getSessionAgentSaverPlugin(): SessionAgentSaverPluginInterface
    {
        return $this->getProvidedDependency(SessionAgentValidationDependencyProvider::PLUGIN_SESSION_AGENT_SAVER);
    }

    public function getSessionAgentValidatorPlugin(): SessionAgentValidatorPluginInterface
    {
        return $this->getProvidedDependency(SessionAgentValidationDependencyProvider::PLUGIN_SESSION_AGENT_VALIDATOR);
    }

    public function getAgentClient(): SessionAgentValidationToAgentClientInterface
    {
        return $this->getProvidedDependency(SessionAgentValidationDependencyProvider::CLIENT_AGENT);
    }

    public function getSessionClient(): SessionAgentValidationToSessionClientInterface
    {
        return $this->getProvidedDependency(SessionAgentValidationDependencyProvider::CLIENT_SESSION);
    }

    public function getSecurityAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->getProvidedDependency(SessionAgentValidationDependencyProvider::SERVICE_SECURITY_AUTHORIZATION_CHECKER);
    }

    public function getRequestStack(): RequestStack
    {
        return $this->getProvidedDependency(SessionAgentValidationDependencyProvider::SERVICE_REQUEST_STACK);
    }
}
