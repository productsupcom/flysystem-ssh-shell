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
class FilesystemReaderTest extends AbstractTestCase
{
    protected Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupRemoteFile();

        $adapter = $this->factory->createAdapter($this->configurator);
        $this->filesystem = new Filesystem($adapter);
    }

    public function testHas()
    {
        $has = $this->filesystem->fileExists(static::REMOTE_NAME);
        $hasNot = $this->filesystem->fileExists(static::REMOTE_NEWPATH);

        $this->assertTrue($has);
        $this->assertFalse($hasNot);
    }

    public function testReadStream()
    {
        $stream = $this->filesystem->readStream(static::REMOTE_NAME);

        $this->assertIsResource($stream);

        $content = \stream_get_contents($stream);

        $this->assertEquals($content, \file_get_contents(static::REMOTE_FILE));
    }

    public function testListContents()
    {
        $content = $this->filesystem->listContents(static::REMOTE_PATH_NAME, true);

        $this->assertNotEmpty($content);
        $this->assertCount(3, $content);

        $fileInfo = $content[1];

        $this->assertEquals('file', $fileInfo['type']);
        $this->assertEquals('remote.txt', $fileInfo['path']);
        $this->assertNotEmpty($fileInfo['timestamp']);
        $this->assertEquals(70, $fileInfo['size']);
        $this->assertEquals('/', $fileInfo['dirname']);
        $this->assertEquals('remote.txt', $fileInfo['basename']);
        $this->assertEquals('txt', $fileInfo['extension']);
        $this->assertEquals('remote', $fileInfo['filename']);
    }

    public function testGetMimetype()
    {
        $mimeType = $this->filesystem->mimeType(static::REMOTE_NAME);

        $this->assertEquals('text/plain', $mimeType);
    }

    public function testGetTimestamp()
    {
        $expected = time();
        touch(static::REMOTE_FILE, $expected);

        $timestamp = $this->filesystem->lastModified(static::REMOTE_NAME);

        $this->assertEquals($expected, $timestamp);
    }

    public function testGetVisibility()
    {
        $visibility = $this->filesystem->visibility(static::REMOTE_NAME);

        $this->assertEquals(Visibility::PUBLIC, $visibility);
    }

    public function testGetSize()
    {
        $size = $this->filesystem->fileSize(static::REMOTE_NAME);

        $this->assertEquals(70, $size);
    }
}
