<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShopTest\Yves\SessionAgentValidation\EventSubscriber;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SessionEntityRequestTransfer;
use Generated\Shared\Transfer\SessionEntityResponseTransfer;
use Generated\Shared\Transfer\UserTransfer;
use SprykerShop\Yves\SessionAgentValidation\EventSubscriber\SaveAgentSessionEventSubscriber;
use SprykerShop\Yves\SessionAgentValidationExtension\Dependency\Plugin\SessionAgentSaverPluginInterface;
use SprykerShopTest\Yves\SessionAgentValidation\SessionAgentValidationYvesTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerShopTest
 * @group Yves
 * @group SessionAgentValidation
 * @group EventSubscriber
 * @group SaveAgentSessionEventSubscriberTest
 * Add your own group annotations below this line
 */
class SaveAgentSessionEventSubscriberTest extends Unit
{
    /**
     * @uses \SprykerShop\Yves\SessionAgentValidation\EventSubscriber\SaveAgentSessionEventSubscriber::ROLE_AGENT
     *
     * @var string
     */
    protected const ROLE_AGENT = 'ROLE_AGENT';

    /**
     * @uses \Orm\Zed\User\Persistence\Map\SpyUserTableMap::COL_STATUS_DELETED
     *
     * @var string
     */
    protected const COL_STATUS_DELETED = 'deleted';

    protected const string COL_STATUS_ACTIVE = 'active';

    protected const string FIREWALL_NAME = 'agent';

    protected const int ID_AGENT = 1;

    protected const string ID_SESSION = '1';

    protected const string USER_IDENTIFIER = 'agent@spryker.com';

    protected const int DEFAULT_EVENT_PRIORITY = 0;

    protected SessionAgentValidationYvesTester $tester;

    public function testGetSubscribedEventsShouldReturnEvents(): void
    {
        // Act
        $subscribedEvents = SaveAgentSessionEventSubscriber::getSubscribedEvents();

        // Assert
        $this->assertNotEmpty($subscribedEvents);

        /** @deprecated Exists for Symfony 5 support only. */
        if (!class_exists(LoginSuccessEvent::class)) {
            $this->assertArrayHasKey(SecurityEvents::INTERACTIVE_LOGIN, $subscribedEvents);

            return;
        }

        $this->assertArrayHasKey(LoginSuccessEvent::class, $subscribedEvents);
    }

    public function testOnInteractiveLoginShouldNotSaveSessionAgentDataWhenRequestDoesNotHaveSession(): void
    {
        // Arrange
        $saverPluginMock = $this->createSessionAgentSaverPluginMock();
        $saveAgentSessionEventSubscriber = new SaveAgentSessionEventSubscriber(
            $this->tester->createAgentClientMock(),
            $saverPluginMock,
            $this->tester->createConfigMock(),
            $this->createSessionAuthenticationStrategyMock(),
        );

        $event = new InteractiveLoginEvent(
            $this->createRequestMock(),
            $this->createAuthenticationTokenMock(),
        );

        // Assert
        $saverPluginMock->expects($this->never())->method('saveSession');

        // Act
        $saveAgentSessionEventSubscriber->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginShouldNotSaveSessionAgentDataWhenUserIsNotSet(): void
    {
        // Arrange
        $saverPluginMock = $this->createSessionAgentSaverPluginMock();
        $saveAgentSessionEventSubscriber = new SaveAgentSessionEventSubscriber(
            $this->tester->createAgentClientMock(),
            $saverPluginMock,
            $this->tester->createConfigMock(),
            $this->createSessionAuthenticationStrategyMock(),
        );

        $event = new InteractiveLoginEvent(
            $this->createRequestMock(true),
            $this->createAuthenticationTokenMock(),
        );

        // Assert
        $saverPluginMock->expects($this->never())->method('saveSession');

        // Act
        $saveAgentSessionEventSubscriber->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginShouldNotSaveSessionAgentDataWhenUserDoesNotHaveAgentRole(): void
    {
        // Arrange
        $saverPluginMock = $this->createSessionAgentSaverPluginMock();
        $saveAgentSessionEventSubscriber = $this->createSaveAgentSessionEventSubscriberWithActiveAgent($saverPluginMock);

        $event = new InteractiveLoginEvent(
            $this->createRequestMock(true),
            $this->createAuthenticationTokenMock(
                $this->createUserMock(),
            ),
        );

        // Assert
        $saverPluginMock->expects($this->never())->method('saveSession');

        // Act
        $saveAgentSessionEventSubscriber->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginShouldNotSaveSessionAgentDataWhenAgentIsNotFound(): void
    {
        // Arrange
        $saverPluginMock = $this->createSessionAgentSaverPluginMock();
        $saveAgentSessionEventSubscriber = new SaveAgentSessionEventSubscriber(
            $this->tester->createAgentClientMock(),
            $saverPluginMock,
            $this->tester->createConfigMock(),
            $this->createSessionAuthenticationStrategyMock(),
        );

        $event = new InteractiveLoginEvent(
            $this->createRequestMock(true),
            $this->createAuthenticationTokenMock(
                $this->createUserMock([static::ROLE_AGENT]),
            ),
        );

        // Assert
        $saverPluginMock->expects($this->never())->method('saveSession');

        // Act
        $saveAgentSessionEventSubscriber->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginShouldNotSaveSessionAgentDataWhenAgentDoesNotHaveId(): void
    {
        // Arrange
        $saverPluginMock = $this->createSessionAgentSaverPluginMock();
        $saveAgentSessionEventSubscriber = new SaveAgentSessionEventSubscriber(
            $this->tester->createAgentClientMock(new UserTransfer()),
            $saverPluginMock,
            $this->tester->createConfigMock(),
            $this->createSessionAuthenticationStrategyMock(),
        );

        $event = new InteractiveLoginEvent(
            $this->createRequestMock(true),
            $this->createAuthenticationTokenMock(
                $this->createUserMock([static::ROLE_AGENT]),
            ),
        );

        // Assert
        $saverPluginMock->expects($this->never())->method('saveSession');

        // Act
        $saveAgentSessionEventSubscriber->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginShouldSaveSessionAgentDataWhenAgentHasId(): void
    {
        // Arrange
        $saverPluginMock = $this->createSessionAgentSaverPluginMock();
        $saveAgentSessionEventSubscriber = new SaveAgentSessionEventSubscriber(
            $this->tester->createAgentClientMock((new UserTransfer())->setIdUser(static::ID_AGENT)->setStatus(static::COL_STATUS_ACTIVE)),
            $saverPluginMock,
            $this->tester->createConfigMock(),
            $this->createSessionAuthenticationStrategyMock(),
        );

        $event = new InteractiveLoginEvent(
            $this->createRequestMock(true),
            $this->createAuthenticationTokenMock(
                $this->createUserMock([static::ROLE_AGENT]),
            ),
        );

        // Assert
        $saverPluginMock->expects($this->once())->method('saveSession');

        // Act
        $saveAgentSessionEventSubscriber->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginShouldNotSaveSessionAgentDataWhenAgentHasInactiveStatus(): void
    {
        // Arrange
        $saverPluginMock = $this->getMockBuilder(SessionAgentSaverPluginInterface::class)->getMock();
        $saveAgentSessionEventSubscriber = new SaveAgentSessionEventSubscriber(
            $this->tester->createAgentClientMock((new UserTransfer())->setIdUser(1)->setStatus(static::COL_STATUS_DELETED)),
            $saverPluginMock,
            $this->tester->createConfigMock(),
            $this->createSessionAuthenticationStrategyMock(),
        );

        $event = new InteractiveLoginEvent(
            $this->createRequestMock(true),
            $this->createAuthenticationTokenMock(
                $this->createUserMock([static::ROLE_AGENT]),
            ),
        );

        // Assert
        $saverPluginMock->expects($this->never())->method('saveSession');

        // Act
        $saveAgentSessionEventSubscriber->onInteractiveLogin($event);
    }

    public function testOnLoginSuccessShouldSaveSessionAgentDataWhenUserIsActiveAgent(): void
    {
        // Arrange
        $saverPluginMock = $this->createSessionAgentSaverPluginMock();
        $saveAgentSessionEventSubscriber = $this->createSaveAgentSessionEventSubscriberWithActiveAgent($saverPluginMock);

        $event = $this->createLoginSuccessEvent($this->createUserMock([static::ROLE_AGENT]));

        // Assert
        $saverPluginMock->expects($this->once())
            ->method('saveSession')
            ->with($this->callback(function (SessionEntityRequestTransfer $sessionEntityRequestTransfer): bool {
                return $sessionEntityRequestTransfer->getIdEntity() === static::ID_AGENT
                    && $sessionEntityRequestTransfer->getIdSession() === static::ID_SESSION;
            }));

        // Act
        $saveAgentSessionEventSubscriber->onLoginSuccess($event);
    }

    public function testOnLoginSuccessShouldNotSaveSessionAgentDataWhenUserDoesNotHaveAgentRole(): void
    {
        // Arrange
        $saverPluginMock = $this->createSessionAgentSaverPluginMock();
        $saveAgentSessionEventSubscriber = $this->createSaveAgentSessionEventSubscriberWithActiveAgent($saverPluginMock);

        $event = $this->createLoginSuccessEvent($this->createUserMock());

        // Assert
        $saverPluginMock->expects($this->never())->method('saveSession');

        // Act
        $saveAgentSessionEventSubscriber->onLoginSuccess($event);
    }

    public function testGetSubscribedEventsShouldContainSwitchUserEvent(): void
    {
        if (!class_exists(LoginSuccessEvent::class)) {
            $this->markTestSkipped('The switch user session save exists only for Symfony 6+.');
        }

        // Act
        $subscribedEvents = SaveAgentSessionEventSubscriber::getSubscribedEvents();

        // Assert
        $this->assertArrayHasKey(SecurityEvents::SWITCH_USER, $subscribedEvents);
    }

    public function testOnSwitchUserShouldSaveSessionOfOriginalAgentWhenImpersonationStarts(): void
    {
        // Arrange
        $saverPluginMock = $this->createSessionAgentSaverPluginMock();
        $saveAgentSessionEventSubscriber = $this->createSaveAgentSessionEventSubscriberWithActiveAgent($saverPluginMock);

        $switchUserToken = new SwitchUserToken(
            $this->createUserMock(),
            static::FIREWALL_NAME,
            [],
            $this->createAuthenticationTokenMock($this->createUserMock([static::ROLE_AGENT])),
        );
        $event = new SwitchUserEvent($this->createRequestMock(true), $this->createUserMock(), $switchUserToken);

        // Assert
        $saverPluginMock->expects($this->once())
            ->method('saveSession')
            ->with($this->callback(function (SessionEntityRequestTransfer $sessionEntityRequestTransfer): bool {
                return $sessionEntityRequestTransfer->getIdEntity() === static::ID_AGENT
                    && $sessionEntityRequestTransfer->getIdSession() === static::ID_SESSION;
            }));

        // Act
        $saveAgentSessionEventSubscriber->onSwitchUser($event);
    }

    public function testOnSwitchUserShouldSaveSessionOfTargetAgentWhenImpersonationEnds(): void
    {
        // Arrange
        $saverPluginMock = $this->createSessionAgentSaverPluginMock();
        $saveAgentSessionEventSubscriber = $this->createSaveAgentSessionEventSubscriberWithActiveAgent($saverPluginMock);

        $agentUser = $this->createUserMock([static::ROLE_AGENT]);
        $event = new SwitchUserEvent($this->createRequestMock(true), $agentUser, $this->createAuthenticationTokenMock($agentUser));

        // Assert
        $saverPluginMock->expects($this->once())
            ->method('saveSession')
            ->with($this->callback(function (SessionEntityRequestTransfer $sessionEntityRequestTransfer): bool {
                return $sessionEntityRequestTransfer->getIdEntity() === static::ID_AGENT
                    && $sessionEntityRequestTransfer->getIdSession() === static::ID_SESSION;
            }));

        // Act
        $saveAgentSessionEventSubscriber->onSwitchUser($event);
    }

    public function testOnSwitchUserShouldNotSaveSessionWhenOriginalUserIsNotAgent(): void
    {
        // Arrange
        $saverPluginMock = $this->createSessionAgentSaverPluginMock();
        $saveAgentSessionEventSubscriber = $this->createSaveAgentSessionEventSubscriberWithActiveAgent($saverPluginMock);

        $switchUserToken = new SwitchUserToken(
            $this->createUserMock(),
            static::FIREWALL_NAME,
            [],
            $this->createAuthenticationTokenMock($this->createUserMock()),
        );
        $event = new SwitchUserEvent($this->createRequestMock(true), $this->createUserMock(), $switchUserToken);

        // Assert
        $saverPluginMock->expects($this->never())->method('saveSession');

        // Act
        $saveAgentSessionEventSubscriber->onSwitchUser($event);
    }

    public function testGetSubscribedEventsShouldSubscribeSwitchUserWithPriorityHigherThanDefault(): void
    {
        if (!class_exists(LoginSuccessEvent::class)) {
            $this->markTestSkipped('The switch user session save exists only for Symfony 6+.');
        }

        // Act
        $subscribedEvents = SaveAgentSessionEventSubscriber::getSubscribedEvents();

        // Assert
        $this->assertIsArray($subscribedEvents[SecurityEvents::SWITCH_USER]);
        $this->assertGreaterThan(static::DEFAULT_EVENT_PRIORITY, $subscribedEvents[SecurityEvents::SWITCH_USER][1]);
    }

    /**
     * Rotating and re-saving happen in the same subscriber, so the stored agent session ID always matches the
     * rotated session, whichever spryker/security version the project runs.
     */
    public function testOnSwitchUserShouldSaveRegeneratedSessionIdOfAgent(): void
    {
        // Arrange
        $request = $this->createRequestWithPreviousSession();
        $preSwitchSessionId = $request->getSession()->getId();
        $sessionIdSeenBySaver = null;

        $saverPluginMock = $this->createSessionAgentSaverPluginMock();
        $saverPluginMock->method('saveSession')
            ->willReturnCallback(function (SessionEntityRequestTransfer $sessionEntityRequestTransfer) use (&$sessionIdSeenBySaver): SessionEntityResponseTransfer {
                $sessionIdSeenBySaver = $sessionEntityRequestTransfer->getIdSession();

                return new SessionEntityResponseTransfer();
            });

        $sessionAuthenticationStrategyMock = $this->createSessionAuthenticationStrategyMock();
        $sessionAuthenticationStrategyMock->expects($this->once())
            ->method('onAuthentication')
            ->willReturnCallback(function (Request $request): void {
                $request->getSession()->migrate(true);
            });

        $saveAgentSessionEventSubscriber = new SaveAgentSessionEventSubscriber(
            $this->tester->createAgentClientMock((new UserTransfer())->setIdUser(static::ID_AGENT)->setStatus(static::COL_STATUS_ACTIVE)),
            $saverPluginMock,
            $this->tester->createConfigMock(),
            $sessionAuthenticationStrategyMock,
        );

        $switchUserToken = new SwitchUserToken(
            $this->createUserMock(),
            static::FIREWALL_NAME,
            [],
            $this->createAuthenticationTokenMock($this->createUserMock([static::ROLE_AGENT])),
        );

        // Act
        $saveAgentSessionEventSubscriber->onSwitchUser(new SwitchUserEvent($request, $this->createUserMock(), $switchUserToken));

        // Assert
        $this->assertNotSame($preSwitchSessionId, $request->getSession()->getId());
        $this->assertSame($request->getSession()->getId(), $sessionIdSeenBySaver);
    }

    public function testOnSwitchUserShouldNotRegenerateSessionWhenOriginalUserIsNotAgent(): void
    {
        // Arrange
        $sessionAuthenticationStrategyMock = $this->createSessionAuthenticationStrategyMock();
        $saveAgentSessionEventSubscriber = new SaveAgentSessionEventSubscriber(
            $this->tester->createAgentClientMock((new UserTransfer())->setIdUser(static::ID_AGENT)->setStatus(static::COL_STATUS_ACTIVE)),
            $this->createSessionAgentSaverPluginMock(),
            $this->tester->createConfigMock(),
            $sessionAuthenticationStrategyMock,
        );

        $switchUserToken = new SwitchUserToken(
            $this->createUserMock(),
            static::FIREWALL_NAME,
            [],
            $this->createAuthenticationTokenMock($this->createUserMock()),
        );
        $event = new SwitchUserEvent($this->createRequestWithPreviousSession(), $this->createUserMock(), $switchUserToken);

        // Assert
        $sessionAuthenticationStrategyMock->expects($this->never())->method('onAuthentication');

        // Act
        $saveAgentSessionEventSubscriber->onSwitchUser($event);
    }

    public function testOnSwitchUserShouldNotRegenerateSessionWhenRequestHasNoPreviousSession(): void
    {
        // Arrange
        $sessionAuthenticationStrategyMock = $this->createSessionAuthenticationStrategyMock();
        $saveAgentSessionEventSubscriber = new SaveAgentSessionEventSubscriber(
            $this->tester->createAgentClientMock((new UserTransfer())->setIdUser(static::ID_AGENT)->setStatus(static::COL_STATUS_ACTIVE)),
            $this->createSessionAgentSaverPluginMock(),
            $this->tester->createConfigMock(),
            $sessionAuthenticationStrategyMock,
        );

        $agentUser = $this->createUserMock([static::ROLE_AGENT]);
        $event = new SwitchUserEvent($this->createRequestMock(true), $agentUser, $this->createAuthenticationTokenMock($agentUser));

        // Assert
        $sessionAuthenticationStrategyMock->expects($this->never())->method('onAuthentication');

        // Act
        $saveAgentSessionEventSubscriber->onSwitchUser($event);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\SprykerShop\Yves\SessionAgentValidationExtension\Dependency\Plugin\SessionAgentSaverPluginInterface
     */
    protected function createSessionAgentSaverPluginMock(): SessionAgentSaverPluginInterface
    {
        return $this->createMock(SessionAgentSaverPluginInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface
     */
    protected function createSessionAuthenticationStrategyMock(): SessionAuthenticationStrategyInterface
    {
        return $this->createMock(SessionAuthenticationStrategyInterface::class);
    }

    protected function createRequestWithPreviousSession(): Request
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = new Request();
        $request->setSession($session);
        $request->cookies->set($session->getName(), $session->getId());

        return $request;
    }

    protected function createLoginSuccessEvent(UserInterface $user): LoginSuccessEvent
    {
        return new LoginSuccessEvent(
            $this->createMock(AuthenticatorInterface::class),
            new SelfValidatingPassport(new UserBadge(static::USER_IDENTIFIER, fn (): UserInterface => $user)),
            $this->createAuthenticationTokenMock($user),
            $this->createRequestMock(true),
            null,
            static::FIREWALL_NAME,
        );
    }

    protected function createSaveAgentSessionEventSubscriberWithActiveAgent(SessionAgentSaverPluginInterface $saverPluginMock): SaveAgentSessionEventSubscriber
    {
        return new SaveAgentSessionEventSubscriber(
            $this->tester->createAgentClientMock((new UserTransfer())->setIdUser(static::ID_AGENT)->setStatus(static::COL_STATUS_ACTIVE)),
            $saverPluginMock,
            $this->tester->createConfigMock(),
            $this->createSessionAuthenticationStrategyMock(),
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\HttpFoundation\Request
     */
    protected function createRequestMock(bool $withSession = false): Request
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->getMock();

        $requestMock->method('hasSession')
            ->willReturn($withSession);

        if (!$withSession) {
            return $requestMock;
        }

        $sessionMock = $this->getMockBuilder(SessionInterface::class)
            ->getMock();

        $sessionMock->method('getId')
            ->willReturn('1');

        $requestMock->method('getSession')
            ->willReturn($sessionMock);

        return $requestMock;
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Security\Core\User\UserInterface|null $user
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Security\Core\Authentication\Token\TokenInterface
     */
    protected function createAuthenticationTokenMock(?UserInterface $user = null): TokenInterface
    {
        $authenticationTokenMock = $this->getMockBuilder(TokenInterface::class)
            ->getMock();

        $authenticationTokenMock->method('getUser')
            ->willReturn($user);

        return $authenticationTokenMock;
    }

    /**
     * @param list<string>|array $roles
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Security\Core\User\UserInterface
     */
    protected function createUserMock(array $roles = []): UserInterface
    {
        $userMock = $this->getMockBuilder(UserInterface::class)
            ->getMock();

        $userMock->method('getRoles')
            ->willReturn($roles);

        return $userMock;
    }
}
