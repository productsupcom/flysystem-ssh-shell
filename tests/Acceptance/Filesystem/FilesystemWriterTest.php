<?php

declare(strict_types=1);

namespace TestsPhuxtilFlysystemSshShell\Acceptance\Filesystem;

use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;
use TestsPhuxtilFlysystemSshShell\Helper\AbstractTestCase;

/**
 * @group flysystem-ssh-shell
 * @group acceptance
 * @group filesystem
 */
class FilesystemWriterTest extends AbstractTestCase
{
    protected Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupRemoteFile();

        $adapter = $this->factory->createAdapter($this->configurator);
        $this->filesystem = new Filesystem($adapter);
    }

    public function testWrite()
    {
        $this->filesystem->write(static::REMOTE_NEWPATH_NAME, 'Lorem Ipsum');

        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
        $this->assertEquals('Lorem Ipsum', \file_get_contents(static::REMOTE_NEWPATH_FILE));
    }

    public function testWriteStream()
    {
        $stream = \fopen(static::LOCAL_FILE, 'r');

        $this->filesystem->writeStream(static::REMOTE_NEWPATH_NAME, $stream);

        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
        $this->assertEquals(\file_get_contents(static::LOCAL_FILE), \file_get_contents(static::REMOTE_NEWPATH_FILE));
    }

    public function testPut()
    {
        $this->filesystem->write(static::REMOTE_NAME, 'Lorem Ipsum');

        $this->assertFileExists(static::REMOTE_FILE);
        $this->assertEquals('Lorem Ipsum', \file_get_contents(static::REMOTE_FILE));
    }

    public function testPutStream()
    {
        $stream = \fopen(static::LOCAL_FILE, 'r');

        $this->filesystem->writeStream(static::REMOTE_NAME, $stream);

        $this->assertFileExists(static::REMOTE_FILE);
        $this->assertEquals(\file_get_contents(static::LOCAL_FILE), \file_get_contents(static::REMOTE_FILE));
    }

    public function testUpdate()
    {
        $this->filesystem->write(static::REMOTE_NAME, 'Lorem Ipsum');

        $this->assertFileExists(static::REMOTE_FILE);
        $this->assertEquals('Lorem Ipsum', \file_get_contents(static::REMOTE_FILE));
    }

    public function testUpdateStream()
    {
        $stream = \fopen(static::LOCAL_FILE, 'r');

        $this->filesystem->writeStream(static::REMOTE_NAME, $stream);

        $this->assertFileExists(static::REMOTE_FILE);
        $this->assertEquals(\file_get_contents(static::LOCAL_FILE), \file_get_contents(static::REMOTE_FILE));
    }

    public function testRename()
    {
        $this->filesystem->move(static::REMOTE_NAME, static::REMOTE_NEWPATH_NAME);

        $this->assertFileDoesNotExist(static::REMOTE_FILE);
        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
    }

    public function testCopy()
    {
        $this->filesystem->copy(static::REMOTE_NAME, static::REMOTE_NEWPATH_NAME);
        $this->assertFileExists(static::REMOTE_NEWPATH_FILE);
    }

    public function testDelete()
    {
        $this->filesystem->delete(static::REMOTE_NAME);
        $this->assertFileDoesNotExist(static::REMOTE_FILE);
    }

    public function testDeleteDir()
    {
        $this->filesystem->createDirectory('/newpath/');
        $this->filesystem->deleteDirectory('/newpath/');

        $this->assertDirectoryDoesNotExist(static::REMOTE_NEWPATH);
    }

    public function testCreateDir()
    {
        $this->filesystem->createDirectory('/newpath/');
        $this->assertDirectoryExists(static::REMOTE_NEWPATH);
    }

    public function testSetVisibility()
    {
        $this->filesystem->setVisibility(static::REMOTE_NAME, Visibility::PRIVATE);
        $visibility = $this->filesystem->visibility(static::REMOTE_NAME);
        $this->assertEquals(Visibility::PRIVATE, $visibility);
    }
}
