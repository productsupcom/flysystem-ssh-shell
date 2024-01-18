<?php

declare(strict_types=1);

namespace TestsPhuxtilFlysystemSshShell\Acceptance\Adapter;

use League\Flysystem\Config;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use TestsPhuxtilFlysystemSshShell\Helper\AbstractTestCase;

/**
 * @group flysystem-ssh-shell
 * @group acceptance
 * @group adapter
 * @group writer
 */
class StreamWriterTest extends AbstractTestCase
{
    public function testWriteStreamShouldSetVisibility()
    {
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $stream = fopen(static::LOCAL_FILE, 'r+');
        $config = new Config();
        $config->extend(['visibility' => Visibility::PRIVATE]);

        $expected = [
            'type' => 'file',
            'size' => \filesize(static::LOCAL_FILE),
            'path' => static::REMOTE_NEWPATH_NAME,
            'visibility' => Visibility::PRIVATE,
        ];

        $result = $adapter->writeStream(static::REMOTE_NEWPATH_NAME, $stream, $config);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->assertEquals($expected, $result);
        $this->assertContent();
    }

    public function testWriteStreamShouldCreatePath()
    {
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $stream = fopen(static::LOCAL_FILE, 'r+');
        $config = new Config();

        $adapter->writeStream(static::REMOTE_NEWPATH_NAME, $stream, $config);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->assertContent();
    }

    public function testWriteStreamShouldThrowExceptionWhenInvalidResource()
    {
        $this->expectException(UnableToWriteFile::class);
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $adapter->writeStream(static::REMOTE_NEWPATH_NAME, false, $config);
    }

    public function testWriteStreamShouldReturnFalseWhenSshCommandFails()
    {
        $this->expectException(UnableToWriteFile::class);

        $this->configurator->setPort(0);
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $stream = fopen(static::LOCAL_FILE, 'r+');
        $config = new Config();

        $adapter->writeStream(static::REMOTE_NEWPATH_NAME, $stream, $config);

        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    public function testUpdateStream()
    {
        $this->setupRemoteFile();

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $stream = fopen(static::LOCAL_FILE, 'r+');
        $config = new Config();

        $adapter->writeStream(static::REMOTE_NAME, $stream, $config);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->assertContent(static::REMOTE_FILE);
    }
}
