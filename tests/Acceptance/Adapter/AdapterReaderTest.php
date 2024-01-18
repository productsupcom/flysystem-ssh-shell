<?php

declare(strict_types=1);

namespace TestsPhuxtilFlysystemSshShell\Acceptance\Adapter;

use League\Flysystem\UnableToReadFile;
use League\Flysystem\Visibility;
use Phuxtil\Flysystem\SshShell\Adapter\SshShellAdapter;
use Phuxtil\SplFileInfo\VirtualSplFileInfo;
use TestsPhuxtilFlysystemSshShell\Helper\AbstractTestCase;

/**
 * @group flysystem-ssh-shell
 * @group acceptance
 * @group adapter
 * @group reader
 */
class AdapterReaderTest extends AbstractTestCase
{

    protected SshShellAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $this->setupRemoteFile();
    }

    public function testHas()
    {
        $this->assertTrue(
            $this->adapter->has(static::REMOTE_NAME)
        );
    }

    public function testGetMetadata()
    {
        $metadata = $this->adapter->getMetadata(static::REMOTE_NAME);
        $expected = [
            'path' => 'remote.txt',
            'filename' => 'remote',
            'basename' => 'remote.txt',
            'extension' => 'txt',
            'realPath' => '/tmp/remote_fs/remote.txt',
            'aTime' => \fileatime(static::REMOTE_FILE),
            'mTime' => \filemtime(static::REMOTE_FILE),
            'cTime' => \filectime(static::REMOTE_FILE),
            'inode' => \fileinode(static::REMOTE_FILE),
            'size' => \filesize(static::REMOTE_FILE),
            'perms' => '0644',
            'owner' => 0,
            'group' => 0,
            'type' => 'file',
            'linkTarget' => -1,
            'writable' => \is_writable(static::REMOTE_FILE),
            'readable' => \is_readable(static::REMOTE_FILE),
            'executable' => false, // always returns true inside docker container \is_executable(static::REMOTE_FILE),
            'file' => \is_file(static::REMOTE_FILE),
            'dir' => \is_dir(static::REMOTE_FILE),
            'link' => \is_link(static::REMOTE_FILE),
            'visibility' => Visibility::PUBLIC,
            'timestamp' => \filemtime(static::REMOTE_FILE),
            'mimetype' => 'text/plain',
            'dirname' => '/',
        ];

        $this->assertEquals(
            $expected,
            $metadata
        );
    }

    public function testGetMetadataShouldReturnFalse()
    {
        $metadata = $this->adapter->getMetadata(static::REMOTE_INVALID_NAME);

        $this->assertFalse($metadata);
    }

    public function testGetSize()
    {
        $this->assertEquals(
            \filesize(static::REMOTE_FILE),
            $this->adapter->fileSize(static::REMOTE_NAME)['size']
        );
    }

    public function testGetMimetype()
    {
        $this->assertEquals(
            'text/plain',
            $this->adapter->mimeType(static::REMOTE_NAME)['mimetype']
        );
    }

    public function testGetTimestamp()
    {
        $this->assertEquals(
            filemtime(static::REMOTE_FILE),
            $this->adapter->lastModified(static::REMOTE_NAME)['timestamp']
        );
    }

    public function testGetVisibility()
    {
        $this->assertEquals(
            Visibility::PUBLIC,
            $this->adapter->visibility(static::REMOTE_NAME)['visibility']
        );
    }

    public function testRead()
    {
        $result = $this->adapter->read(static::REMOTE_NAME);

        $this->assertEquals(
            \file_get_contents(static::REMOTE_FILE),
            $result
        );
    }

    public function testReadShouldReturnFalseWhenSshCommandFails()
    {
        $this->expectException(UnableToReadFile::class);
        $this->configurator->setPort(0);
        $adapter = $this->factory->createAdapter($this->configurator);

        $adapter->read(static::REMOTE_NAME);
    }

    public function testReadShouldReturnFalseWhenInvalidPath()
    {
        $this->expectException(UnableToReadFile::class);
        $this->adapter->read(static::REMOTE_INVALID_NAME);
    }

    public function testListContents()
    {
        $result = $this->adapter->listContents(static::REMOTE_PATH_NAME);

        foreach ($result as $item) {
            $expected = new \SplFileInfo($item['realPath']);
            $info = (new VirtualSplFileInfo($item['realPath']))
                ->fromArray($item);

            $this->assertOutput($expected, $info);
        }
    }

    public function testListContentsShouldReturnEmptyArrayWhenSshCommandFails()
    {
        $this->configurator->setPort(0);
        $adapter = $this->factory->createAdapter($this->configurator);

        $result = $adapter->listContents(static::REMOTE_PATH_NAME);

        $this->assertCount(0, $result);
    }

    public function testListContentsRecursively()
    {
        $result = $this->adapter->listContents(static::REMOTE_PATH_NAME, true);

        foreach ($result as $item) {
            $expected = new \SplFileInfo($item['realPath']);
            $info = (new VirtualSplFileInfo($item['realPath']))
                ->fromArray($item);

            $this->assertOutput($expected, $info);
        }
    }

    protected function assertOutput(\SplFileInfo $expected, \SplFileInfo $info)
    {
        $octal = substr(sprintf('%o', fileperms($expected->getPathname())), -4);

        // links are resolved by find, however fileperms() and filetype() will return link info
        if ($expected->isLink()) {
            $linkTargetInfo = new \SplFileInfo($expected->getRealPath());
            $this->assertEquals($linkTargetInfo->getType(), $info->getType());
            $this->assertEquals($linkTargetInfo->isLink(), $info->isLink());
            $this->assertEquals($expected->getPathname(), $info->getRealPath());
        } else {
            $this->assertEquals($expected->getType(), $info->getType());
            $this->assertEquals($expected->isLink(), $info->isLink());
            $this->assertEquals($expected->getRealPath(), $info->getRealPath());
        }

        $this->assertEquals($octal, $info->getPerms());
        $this->assertEquals($expected->getOwner(), $info->getOwner());
        $this->assertEquals($expected->getGroup(), $info->getGroup());
        $this->assertEquals($expected->getInode(), $info->getInode());
        $this->assertEquals($expected->getSize(), $info->getSize());
        $this->assertEquals($expected->getFilename(), $info->getFilename());
        $this->assertEquals($expected->getPathname(), $info->getPathname());
        $this->assertEquals($expected->getPath(), $info->getPath());
        $this->assertEquals($expected->getBasename(), $info->getBasename());
        $this->assertEquals($expected->getExtension(), $info->getExtension());
        $this->assertLessThanOrEqual($expected->getATime(), $info->getATime());
        $this->assertEquals($expected->getMTime(), $info->getMTime());
        $this->assertEquals($expected->getCTime(), $info->getCTime());
        $this->assertEquals($expected->isFile(), $info->isFile());
        $this->assertEquals($expected->isDir(), $info->isDir());
        $this->assertEquals($expected->isReadable(), $info->isReadable());
        $this->assertEquals($expected->isWritable(), $info->isWritable());
        // $this->assertEquals($expected->isExecutable(), $info->isExecutable()); ///always returns true inside docker container \is_executable(static::REMOTE_FILE),
    }
}
