<?php

declare(strict_types=1);

namespace TestsPhuxtilFlysystemSshShell\Acceptance\Process\Authentication;

use PHPUnit\Framework\TestCase;
use Phuxtil\Flysystem\SshShell\Process\Authentication\Authenticator;
use Phuxtil\Flysystem\SshShell\SshShellConfigurator;

/**
 * @group flysystem-ssh-shell
 * @group acceptance
 * @group process
 */
class AuthenticatorTest extends TestCase
{
    public const SSH_USER = \TESTS_SSH_USER;
    public const SSH_HOST = \TESTS_SSH_HOST;

    public function testGenerateByConfig()
    {
        $configurator = (new SshShellConfigurator())
            ->setUser(static::SSH_HOST)
            ->setHost(static::SSH_USER);

        $authenticator = new Authenticator();
        $auth = $authenticator->generate($configurator);

        $this->assertEquals('', $auth);
    }

    public function testGenerateByPrivateKey()
    {
        $configurator = (new SshShellConfigurator())
            ->setPrivateKey('~/.ssh/id_rsa.data_container');

        $authenticator = new Authenticator();
        $auth = $authenticator->generate($configurator);

        $this->assertEquals('-i ~/.ssh/id_rsa.data_container', $auth);
    }

    public function testGenerateByConfigShouldThrowException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown authentication type: invalid');

        $configurator = (new SshShellConfigurator())
            ->setUser(static::SSH_HOST)
            ->setHost(static::SSH_USER)
            ->setAuthType('invalid');

        $authenticator = new Authenticator();
        $authenticator->generate($configurator);
    }
}
