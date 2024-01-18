<?php

declare(strict_types=1);

namespace TestsPhuxtilFlysystemSshShell\Acceptance\Adapter;

use League\Flysystem\Config;
use League\Flysystem\Visibility;
use TestsPhuxtilFlysystemSshShell\Helper\AbstractTestCase;

/**
 * @group flysystem-ssh-shell
 * @group acceptance
 * @group adapter
 * @group writer
 */
class AdapterWriterTest extends AbstractTestCase
{
    public function testWriteStreamShouldSetVisibility()
    {
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $config->extend(['visibility' => Visibility::PRIVATE]);

        $result = $adapter->write(static::REMOTE_NEWPATH_NAME, 'FooBaroo', $config);

        $expected = [
            'contents' => 'FooBaroo',
            'type' => 'file',
            'size' => strlen('FooBaroo'),
            'path' => static::REMOTE_NEWPATH_NAME,
            'visibility' => Visibility::PRIVATE,
        ];

        $this->assertEquals($expected, $result);
        $this->assertEquals('FooBaroo', \file_get_contents(static::REMOTE_NEWPATH_FILE));
        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
    }

    public function testWriteShouldCreatePath()
    {
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $result = $adapter->write(static::REMOTE_NEWPATH_NAME, 'FooBaroo', $config);

        $expected = [
            'contents' => 'FooBaroo',
            'type' => 'file',
            'size' => strlen('FooBaroo'),
            'path' => static::REMOTE_NEWPATH_NAME,
        ];

        $this->assertEquals($expected, $result);
        $this->assertEquals('FooBaroo', \file_get_contents(static::REMOTE_NEWPATH_FILE));
        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
    }

    public function testWriteShouldCreatePathWithPrivateKeyAuth()
    {
        $this->configurator->setPrivateKey('~/.ssh/id_rsa.data_container');

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $result = $adapter->write(static::REMOTE_NEWPATH_NAME, 'FooBaroo', $config);

        $expected = [
            'contents' => 'FooBaroo',
            'type' => 'file',
            'size' => strlen('FooBaroo'),
            'path' => static::REMOTE_NEWPATH_NAME,
        ];

        $this->assertEquals($expected, $result);
        $this->assertEquals('FooBaroo', \file_get_contents(static::REMOTE_NEWPATH_FILE));
        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
    }

    public function testWriteShouldReturnFalse()
    {
        $this->configurator->setPort(0);
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $result = $adapter->write(static::REMOTE_NEWPATH_NAME, 'FooBaroo', $config);

        $this->assertFalse($result);
    }

    public function testUpdateShouldNotChangeMeta()
    {
        $this->setupRemoteFile();

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $result = $adapter->update(static::REMOTE_NAME, 'FooBaroo', $config);

        $expected = [
            'contents' => 'FooBaroo',
            'type' => 'file',
            'size' => strlen('FooBaroo'),
            'path' => static::REMOTE_NAME,
        ];

        $this->assertEquals($expected, $result);
        $this->assertEquals('FooBaroo', \file_get_contents(static::REMOTE_FILE));
        $this->assertFileExists(static::REMOTE_FILE);
    }

    public function testUpdateShouldChangeVisibility()
    {
        $this->setupRemoteFile();

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $config->extend(['visibility' => Visibility::PRIVATE]);
        $result = $adapter->update(static::REMOTE_NAME, 'FooBaroo', $config);

        $expected = [
            'contents' => 'FooBaroo',
            'type' => 'file',
            'size' => strlen('FooBaroo'),
            'path' => static::REMOTE_NAME,
            'visibility' => Visibility::PRIVATE,
        ];

        $this->assertEquals($expected, $result);
        $this->assertEquals('FooBaroo', \file_get_contents(static::REMOTE_FILE));
        $this->assertFileExists(static::REMOTE_FILE);
    }

    public function testUpdateShouldReturnFalseWhenPathDoesNotExist()
    {
        $this->assertFileDoesNotExist(static::REMOTE_INVALID_PATH);

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $result = $adapter->update(static::REMOTE_INVALID_NAME, 'FooBaroo', $config);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist(static::REMOTE_INVALID_PATH);
    }

    public function testMkdir()
    {
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $result = $adapter->createDir('/newpath/', $config);

        $expected = [
            'path' => '/newpath/',
            'type' => 'dir',
        ];

        $this->assertEquals($expected, $result);
        $this->assertDirectoryExists(static::REMOTE_NEWPATH);
    }

    public function testCopy()
    {
        $this->setupRemoteFile();
        $this->assertFileDoesNotExist(static::REMOTE_NEWPATH_FILE);

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $result = $adapter->copy(static::REMOTE_NAME, static::REMOTE_NEWPATH_NAME);

        $this->assertTrue($result);
        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
    }

    public function testCopyShouldReturnFalseWhenSshCommandFails()
    {
        $this->setupRemoteFile();
        $this->assertFileDoesNotExist(static::REMOTE_NEWPATH_FILE);

        $this->configurator->setPort(0);
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $result = $adapter->copy(static::REMOTE_NAME, static::REMOTE_NEWPATH_NAME);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist(static::REMOTE_NEWPATH_FILE);
    }

    public function testRename()
    {
        $this->setupRemoteFile();

        $fileToMove = $this->setupRemoteTempFile();
        $fileToMoveName = \basename($fileToMove);
        $this->assertFileDoesNotExist(static::REMOTE_NEWPATH_FILE);

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $result = $adapter->rename($fileToMoveName, static::REMOTE_NEWPATH_NAME);

        $this->assertTrue($result);
        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
        $this->assertFileDoesNotExist($fileToMove);
    }

    public function testRenameShouldReturnFalseWhenSshProcessFails()
    {
        $fileToMove = $this->setupRemoteTempFile();
        $fileToMoveName = \basename($fileToMove);

        $this->assertFileDoesNotExist(static::REMOTE_NEWPATH_FILE);

        $this->configurator->setPort(0);
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $result = $adapter->rename($fileToMoveName, static::REMOTE_NEWPATH_NAME);

        $this->assertFalse($result);
        $this->assertFileExists($fileToMove);
        $this->assertFileDoesNotExist(static::REMOTE_NEWPATH_FILE);
    }

    public function testDelete()
    {
        $fileToMove = $this->setupRemoteTempFile();
        $fileToMoveName = \basename($fileToMove);

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $result = $adapter->delete($fileToMoveName);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($fileToMoveName);
    }

    public function testDeleteDir()
    {
        $dir = $this->setupRemoteTempDir();
        $dirName = \basename($dir);

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $result = $adapter->deleteDir($dirName);

        $this->assertTrue($result);
        $this->assertDirectoryDoesNotExist($dirName);
    }
}
