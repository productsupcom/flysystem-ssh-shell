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

        $result = $adapter->writeStream(static::REMOTE_NEWPATH_NAME, $stream, $config);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $expected = [
            'type' => 'file',
            'size' => \filesize(static::LOCAL_FILE),
            'path' => static::REMOTE_NEWPATH_NAME,
        ];

        $this->assertEquals($expected, $result);
        $this->assertContent();
    }

    public function testWriteStreamShouldReturnFalseWhenInvalidResource()
    {
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $config = new Config();
        $result = $adapter->writeStream(static::REMOTE_NEWPATH_NAME, false, $config);

        $this->assertFalse($result);
    }

    public function testWriteStreamShouldReturnFalseWhenSshCommandFails()
    {
        $this->configurator->setPort(0);
        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $stream = fopen(static::LOCAL_FILE, 'r+');
        $config = new Config();

        $result = $adapter->writeStream(static::REMOTE_NEWPATH_NAME, $stream, $config);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->assertFalse($result);
    }

    public function testUpdateStream()
    {
        $this->setupRemoteFile();

        $adapter = $this->factory->createAdapter(
            $this->configurator
        );

        $stream = fopen(static::LOCAL_FILE, 'r+');
        $config = new Config();

        $result = $adapter->updateStream(static::REMOTE_NAME, $stream, $config);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $expected = [
            'type' => 'file',
            'size' => \filesize(static::LOCAL_FILE),
            'path' => static::REMOTE_NAME,
        ];

        $this->assertEquals($expected, $result);
        $this->assertContent(static::REMOTE_FILE);
    }
}
