<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShopTest\Yves\SessionAgentValidation\Plugin\CustomerPage;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SessionEntityRequestTransfer;
use Generated\Shared\Transfer\UserTransfer;
use SprykerShop\Yves\SessionAgentValidation\Dependency\Client\SessionAgentValidationToAgentClientInterface;
use SprykerShop\Yves\SessionAgentValidation\Dependency\Client\SessionAgentValidationToSessionClientInterface;
use SprykerShop\Yves\SessionAgentValidation\Plugin\CustomerPage\UpdateAgentSessionAfterCustomerAuthenticationSuccessPlugin;
use SprykerShop\Yves\SessionAgentValidation\SessionAgentValidationDependencyProvider;
use SprykerShop\Yves\SessionAgentValidationExtension\Dependency\Plugin\SessionAgentSaverPluginInterface;
use SprykerShopTest\Yves\SessionAgentValidation\SessionAgentValidationYvesTester;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerShopTest
 * @group Yves
 * @group SessionAgentValidation
 * @group Plugin
 * @group CustomerPage
 * @group UpdateAgentSessionAfterCustomerAuthenticationSuccessPluginTest
 * Add your own group annotations below this line
 */
class UpdateAgentSessionAfterCustomerAuthenticationSuccessPluginTest extends Unit
{
    protected const int ID_AGENT = 1;

    protected const string ID_SESSION = '1';

    protected SessionAgentValidationYvesTester $tester;

    public function testExecuteShouldNotUpdateSessionAgentDataWhenAuthorizationIsNotGranted(): void
    {
        // Arrange
        $sessionAgentSaverPluginMock = $this->createSessionAgentSaverPluginMock();
        $this->addDependencies(
            $this->createAuthorizationCheckerMock(),
            $this->tester->createAgentClientMock((new UserTransfer())->setIdUser(static::ID_AGENT)),
            $sessionAgentSaverPluginMock,
        );

        $updateAgentSessionAfterCustomerAuthenticationSuccessPlugin = new UpdateAgentSessionAfterCustomerAuthenticationSuccessPlugin();

        // Assert
        $sessionAgentSaverPluginMock->expects($this->never())
            ->method('saveSession');

        // Act
        $updateAgentSessionAfterCustomerAuthenticationSuccessPlugin->execute();
    }

    public function testExecuteShouldNotUpdateSessionAgentDataWhenAgentIsNotLoggedIn(): void
    {
        // Arrange
        $sessionAgentSaverPluginMock = $this->createSessionAgentSaverPluginMock();

        $this->addDependencies(
            $this->createAuthorizationCheckerMock(true),
            $this->tester->createAgentClientMock(),
            $sessionAgentSaverPluginMock,
        );

        $updateAgentSessionAfterCustomerAuthenticationSuccessPlugin = new UpdateAgentSessionAfterCustomerAuthenticationSuccessPlugin();

        // Assert
        $sessionAgentSaverPluginMock->expects($this->never())
            ->method('saveSession');

        // Act
        $updateAgentSessionAfterCustomerAuthenticationSuccessPlugin->execute();
    }

    public function testExecuteShouldUpdateSessionAgentDataWhenAuthorizationIsGrantedAndAgentIsLoggedIn(): void
    {
        // Arrange
        $sessionAgentSaverPluginMock = $this->createSessionAgentSaverPluginMock();

        $this->addDependencies(
            $this->createAuthorizationCheckerMock(true),
            $this->tester->createAgentClientMock((new UserTransfer())->setIdUser(static::ID_AGENT)),
            $sessionAgentSaverPluginMock,
        );

        $updateAgentSessionAfterCustomerAuthenticationSuccessPlugin = new UpdateAgentSessionAfterCustomerAuthenticationSuccessPlugin();

        // Assert
        $sessionAgentSaverPluginMock->expects($this->once())
            ->method('saveSession')
            ->with($this->callback(function (SessionEntityRequestTransfer $sessionEntityRequestTransfer): bool {
                return $sessionEntityRequestTransfer->getIdEntity() === static::ID_AGENT
                    && $sessionEntityRequestTransfer->getIdSession() === static::ID_SESSION;
            }));

        // Act
        $updateAgentSessionAfterCustomerAuthenticationSuccessPlugin->execute();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\SprykerShop\Yves\SessionAgentValidationExtension\Dependency\Plugin\SessionAgentSaverPluginInterface
     */
    protected function createSessionAgentSaverPluginMock(): SessionAgentSaverPluginInterface
    {
        return $this->createMock(SessionAgentSaverPluginInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     */
    protected function createAuthorizationCheckerMock(bool $isGranted = false): AuthorizationCheckerInterface
    {
        $authorizationCheckerMock = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->getMock();

        $authorizationCheckerMock->method('isGranted')
            ->willReturn($isGranted);

        return $authorizationCheckerMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\SprykerShop\Yves\SessionAgentValidation\Dependency\Client\SessionAgentValidationToSessionClientInterface
     */
    protected function createSessionClientMock(): SessionAgentValidationToSessionClientInterface
    {
        $sessionClientMock = $this->getMockBuilder(SessionAgentValidationToSessionClientInterface::class)
            ->getMock();

        $sessionClientMock->method('getId')
            ->willReturn(static::ID_SESSION);

        return $sessionClientMock;
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker
     * @param \PHPUnit\Framework\MockObject\MockObject|\SprykerShop\Yves\SessionAgentValidation\Dependency\Client\SessionAgentValidationToAgentClientInterface $agentClient
     * @param \PHPUnit\Framework\MockObject\MockObject|\SprykerShop\Yves\SessionAgentValidationExtension\Dependency\Plugin\SessionAgentSaverPluginInterface $sessionAgentSaverPluginMock
     */
    protected function addDependencies(
        AuthorizationCheckerInterface $authorizationChecker,
        SessionAgentValidationToAgentClientInterface $agentClient,
        SessionAgentSaverPluginInterface $sessionAgentSaverPluginMock
    ): void {
        $this->tester->setDependency(SessionAgentValidationDependencyProvider::CLIENT_SESSION, $this->createSessionClientMock());
        $this->tester->setDependency(SessionAgentValidationDependencyProvider::SERVICE_SECURITY_AUTHORIZATION_CHECKER, $authorizationChecker);
        $this->tester->setDependency(SessionAgentValidationDependencyProvider::CLIENT_AGENT, $agentClient);
        $this->tester->setDependency(SessionAgentValidationDependencyProvider::PLUGIN_SESSION_AGENT_SAVER, $sessionAgentSaverPluginMock);
    }
}
