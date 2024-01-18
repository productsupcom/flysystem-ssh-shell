<?php

declare(strict_types=1);

namespace TestsPhuxtilFlysystemSshShell\Acceptance\Adapter;

use League\Flysystem\Config;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToWriteFile;
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

        $adapter->write(static::REMOTE_NEWPATH_NAME, 'FooBaroo', $config);

        $this->assertEquals('FooBaroo', \file_get_contents(static::REMOTE_NEWPATH_FILE));
        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
    }

    public function testWriteShouldCreatePath()
    {
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $adapter->write(static::REMOTE_NEWPATH_NAME, 'FooBaroo', $config);

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
        $adapter->write(static::REMOTE_NEWPATH_NAME, 'FooBaroo', $config);

        $this->assertEquals('FooBaroo', \file_get_contents(static::REMOTE_NEWPATH_FILE));
        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
    }

    public function testWriteShouldThrowException()
    {
        $this->expectException(UnableToWriteFile::class);
        $this->configurator->setPort(0);
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $adapter->write(static::REMOTE_NEWPATH_NAME, 'FooBaroo', $config);
    }

    public function testUpdateShouldNotChangeMeta()
    {
        $this->setupRemoteFile();

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $adapter->write(static::REMOTE_NAME, 'FooBaroo', $config);

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
        $adapter->write(static::REMOTE_NAME, 'FooBaroo', $config);
        $this->assertEquals('FooBaroo', \file_get_contents(static::REMOTE_FILE));
        $this->assertFileExists(static::REMOTE_FILE);
    }

    public function testUpdateShouldThrowExceptionWhenPathDoesNotExist()
    {
        $this->expectException(UnableToWriteFile::class);

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $adapter->write(static::REMOTE_INVALID_NAME, 'FooBaroo', $config);
    }

    public function testMkdir()
    {
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $adapter->createDirectory('/newpath/', $config);

        $this->assertDirectoryExists(static::REMOTE_NEWPATH);
    }

    public function testCopy()
    {
        $this->setupRemoteFile();
        $this->assertFileDoesNotExist(static::REMOTE_NEWPATH_FILE);

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $adapter->copy(static::REMOTE_NAME, static::REMOTE_NEWPATH_NAME, new Config());
        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
    }

    public function testCopyShouldThrowExceptionWhenSshCommandFails()
    {
        $this->expectException(UnableToCopyFile::class);

        $this->configurator->setPort(0);
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $adapter->copy(static::REMOTE_NAME, static::REMOTE_NEWPATH_NAME, new Config());
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

        $adapter->move($fileToMoveName, static::REMOTE_NEWPATH_NAME, new Config());

        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
        $this->assertFileDoesNotExist($fileToMove);
    }

    public function testRenameShouldThrowExceptionWhenSshProcessFails()
    {
        $fileToMove = $this->setupRemoteTempFile();
        $fileToMoveName = \basename($fileToMove);

        $this->expectException(UnableToMoveFile::class);

        $this->configurator->setPort(0);
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $adapter->move($fileToMoveName, static::REMOTE_NEWPATH_NAME, new Config());
    }

    public function testDelete()
    {
        $fileToMove = $this->setupRemoteTempFile();
        $fileToMoveName = \basename($fileToMove);

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $adapter->delete($fileToMoveName);

        $this->assertFileDoesNotExist($fileToMoveName);
    }

    public function testDeleteDir()
    {
        $dir = $this->setupRemoteTempDir();
        $dirName = \basename($dir);

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $adapter->deleteDirectory($dirName);
        $this->assertDirectoryDoesNotExist($dirName);
    }
}
