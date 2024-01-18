<?php

declare(strict_types=1);

namespace TestsPhuxtilFlysystemSshShell\Acceptance\Adapter;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\MountManager;
use TestsPhuxtilFlysystemSshShell\Helper\AbstractTestCase;

/**
 * @group flysystem-ssh-shell
 * @group acceptance
 * @group adapter
 * @group writer
 */
class MountManagerTest extends AbstractTestCase
{
    protected MountManager $mountManager;

    protected function setUp(): void
    {
        parent::setUp();

        $sshAdapter = $this->factory->createAdapter(
            $this->configurator
        );

        $localAdapter = new LocalFilesystemAdapter(
            static::REMOTE_PATH
        );

        $this->mountManager = new MountManager(
            [
                'ssh' => new Filesystem($sshAdapter),
                'local' => new Filesystem($localAdapter),
            ]
        );
    }

    public function testHas()
    {
        $this->setupRemoteFile();

        $hasLocal = $this->mountManager->has('local://'.static::REMOTE_NAME);
        $hasSsh = $this->mountManager->has('ssh://'.static::REMOTE_NAME);

        $this->assertTrue($hasLocal);
        $this->assertTrue($hasSsh);
    }

    /**
     * @return void
     *
     * @throws FilesystemException
     */
    public function testCopy()
    {
        $this->setupRemoteFile();

        $this->mountManager->copy(
            'ssh://'.static::REMOTE_NAME,
            'local://'.static::REMOTE_NEWPATH_NAME
        );

        $this->assertContent();
    }

    public function testMove()
    {
        $this->setupRemoteFile();

        $this->mountManager->move(
            'ssh://'.static::REMOTE_NAME,
            'local://'.static::REMOTE_NEWPATH_NAME
        );

        $this->assertContent();
        $this->assertFileDoesNotExist(static::REMOTE_FILE);
    }
}
