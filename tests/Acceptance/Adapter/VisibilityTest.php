<?php

declare(strict_types=1);

namespace TestsPhuxtilFlysystemSshShell\Acceptance\Adapter;

use League\Flysystem\Visibility;
use TestsPhuxtilFlysystemSshShell\Helper\AbstractTestCase;

/**
 * @group flysystem-ssh-shell
 * @group acceptance
 * @group adapter
 * @group writer
 */
class VisibilityTest extends AbstractTestCase
{
    public function testSetVisibilityToPrivate()
    {
        $this->setupRemoteFile();

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $result = $adapter->setVisibility(static::REMOTE_NAME, Visibility::PRIVATE);

        $expected = [
            'path' => static::REMOTE_NAME,
            'visibility' => Visibility::PRIVATE,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testSetVisibilityToPublic()
    {
        $this->setupRemoteFile();

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $result = $adapter->setVisibility(static::REMOTE_NAME, Visibility::PUBLIC);

        $expected = [
            'path' => static::REMOTE_NAME,
            'visibility' => Visibility::PUBLIC,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testSetVisibilityInvalid()
    {
        $this->setupRemoteFile();

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $result = $adapter->setVisibility(static::REMOTE_NAME, 'invalid');

        $this->assertFalse($result);
    }

    public function testSetVisibilityInvalidPath()
    {
        $this->setupRemoteFile();

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $result = $adapter->setVisibility(static::REMOTE_INVALID_NAME, Visibility::PUBLIC);

        $this->assertFalse($result);
    }
}
