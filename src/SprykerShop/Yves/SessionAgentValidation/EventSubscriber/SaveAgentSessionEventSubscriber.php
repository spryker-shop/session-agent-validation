<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\SessionAgentValidation\EventSubscriber;

use Generated\Shared\Transfer\SessionEntityRequestTransfer;
use Generated\Shared\Transfer\UserTransfer;
use SprykerShop\Yves\SessionAgentValidation\Dependency\Client\SessionAgentValidationToAgentClientInterface;
use SprykerShop\Yves\SessionAgentValidation\SessionAgentValidationConfig;
use SprykerShop\Yves\SessionAgentValidationExtension\Dependency\Plugin\SessionAgentSaverPluginInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

class SaveAgentSessionEventSubscriber implements EventSubscriberInterface
{
    /**
     * @uses \SprykerShop\Yves\AgentPage\Plugin\Security\AgentPageSecurityPlugin::ROLE_AGENT
     *
     * @var string
     */
    protected const ROLE_AGENT = 'ROLE_AGENT';

    /**
     * @uses \Orm\Zed\User\Persistence\Map\SpyUserTableMap::COL_STATUS_ACTIVE
     *
     * @var string
     */
    protected const COL_STATUS_ACTIVE = 'active';

    // Runs before AgentPage's switch-user subscriber, which saves the impersonated customer's session ID at the default priority.
    protected const int SWITCH_USER_EVENT_PRIORITY = 128;

    protected SessionAgentValidationToAgentClientInterface $agentClient;

    protected SessionAgentSaverPluginInterface $sessionAgentSaverPlugin;

    protected SessionAgentValidationConfig $config;

    protected SessionAuthenticationStrategyInterface $sessionAuthenticationStrategy;

    public function __construct(
        SessionAgentValidationToAgentClientInterface $agentClient,
        SessionAgentSaverPluginInterface $sessionAgentSaverPlugin,
        SessionAgentValidationConfig $config,
        SessionAuthenticationStrategyInterface $sessionAuthenticationStrategy
    ) {
        $this->agentClient = $agentClient;
        $this->sessionAgentSaverPlugin = $sessionAgentSaverPlugin;
        $this->config = $config;
        $this->sessionAuthenticationStrategy = $sessionAuthenticationStrategy;
    }

    /**
     * @return array<string, string|array<int, int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        /** @deprecated Exists for Symfony 5 support only. */
        if (!class_exists(LoginSuccessEvent::class)) {
            return [
                SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
            ];
        }

        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            SecurityEvents::SWITCH_USER => ['onSwitchUser', static::SWITCH_USER_EVENT_PRIORITY],
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $this->saveSession($event->getRequest(), $event->getAuthenticationToken()->getUser());
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $this->saveSession($event->getRequest(), $event->getUser());
    }

    public function onSwitchUser(SwitchUserEvent $switchUserEvent): void
    {
        $agentUser = $this->getAgentUser($switchUserEvent);

        if (!$agentUser instanceof UserInterface || !$this->hasAgentRole($agentUser)) {
            return;
        }

        $this->regenerateSession($switchUserEvent);
        $this->saveSession($switchUserEvent->getRequest(), $agentUser);
    }

    protected function regenerateSession(SwitchUserEvent $switchUserEvent): void
    {
        $request = $switchUserEvent->getRequest();
        $token = $switchUserEvent->getToken();

        if ($token === null || !$request->hasPreviousSession()) {
            return;
        }

        $this->sessionAuthenticationStrategy->onAuthentication($request, $token);
    }

    protected function saveSession(Request $request, ?UserInterface $user): void
    {
        if (!$request->hasSession()) {
            return;
        }

        if (!$user instanceof UserInterface || !$this->hasAgentRole($user)) {
            return;
        }

        $userTransfer = $this->agentClient->findAgentByUsername(
            (new UserTransfer())->setUsername($this->getUserIdentifier($user)),
        );

        if ($userTransfer === null || !$userTransfer->getIdUser() || $userTransfer->getStatus() !== static::COL_STATUS_ACTIVE) {
            return;
        }

        $this->sessionAgentSaverPlugin->saveSession(
            (new SessionEntityRequestTransfer())
                ->setIdEntity($userTransfer->getIdUserOrFail())
                ->setIdSession($request->getSession()->getId())
                ->setEntityType($this->config->getSessionEntityType()),
        );
    }

    protected function getAgentUser(SwitchUserEvent $switchUserEvent): ?UserInterface
    {
        $token = $switchUserEvent->getToken();

        if ($token instanceof SwitchUserToken) {
            return $token->getOriginalToken()->getUser();
        }

        return $switchUserEvent->getTargetUser();
    }

    protected function hasAgentRole(UserInterface $user): bool
    {
        return in_array(static::ROLE_AGENT, $user->getRoles(), true);
    }

    protected function getUserIdentifier(UserInterface $user): string
    {
        if ($this->isSymfonyVersion5() === true) {
            return $user->getUsername();
        }

        return $user->getUserIdentifier();
    }

    /**
     * @deprecated Shim for Symfony Security Core 5.x, to be removed when Symfony Security Core dependency becomes 6.x+.
     */
    protected function isSymfonyVersion5(): bool
    {
        return class_exists(AuthenticationProviderManager::class);
    }
}
