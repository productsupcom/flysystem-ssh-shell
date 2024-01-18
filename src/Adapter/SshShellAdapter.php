<?php

declare(strict_types = 1);

namespace Phuxtil\Flysystem\SshShell\Adapter;

use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\Config;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Phuxtil\Flysystem\SshShell\Adapter\VisibilityPermission\VisibilityPermissionConverter;
use Phuxtil\SplFileInfo\VirtualSplFileInfo;
use function error_clear_last;
use function error_get_last;
use function is_dir;
use function is_file;

class SshShellAdapter implements FilesystemAdapter
{
    protected PathPrefixer $pathPrefix;

    protected FinfoMimeTypeDetector $mimeTypeDetector;

    public function __construct(
        protected AdapterReader $reader,
        protected AdapterWriter $writer,
        protected VisibilityPermissionConverter $visibilityConverter,

    ) {
        $this->pathPrefix = new PathPrefixer('/');
        $this->mimeTypeDetector = new FinfoMimeTypeDetector();
    }

    public function setPathPrefix(string $prefix): void
    {
        $this->pathPrefix->stripDirectoryPrefix($prefix);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $location = $this->pathPrefix->prefixPath($path);
        $size = $this->writer->write($location, $contents);

        if ($size === false) {
            throw UnableToWriteFile::atLocation($path, error_get_last()['message'] ?? '');
        }

        $visibility = $this->updatePathVisibility($path, $config);

        if ($visibility === false) {
            throw UnableToSetVisibility::atLocation($path, error_get_last()['message'] ?? '');
        }
    }

    public function writeStream(string $path, $resource, Config $config): void
    {
        $location = $this->pathPrefix->prefixPath($path);
        $size = $this->writer->writeStream($location, $resource);
        if ($size === false) {
            throw UnableToWriteFile::atLocation($path, error_get_last()['message'] ?? '');
        }

        $visibility = $this->updatePathVisibility($path, $config);

        if ($visibility === false) {
            throw UnableToSetVisibility::atLocation($path, error_get_last()['message'] ?? '');
        }
    }

    public function move(string $path, string $newPath, Config $config): void
    {
        $locationPath = $this->pathPrefix->prefixPath($path);
        $locationNewPath = $this->pathPrefix->prefixPath($newPath);

        if (!$this->writer->rename($locationPath, $locationNewPath)) {
            throw UnableToMoveFile::because(error_get_last()['message'] ?? 'unknown reason', $path, $newPath);
        }
    }

    public function copy(string $path, string $newPath, Config $config): void
    {
        $locationPath = $this->pathPrefix->prefixPath($path);
        $locationNewPath = $this->pathPrefix->prefixPath($newPath);

        if (!$this->writer->copy($locationPath, $locationNewPath)) {
            throw UnableToCopyFile::because(error_get_last()['message'] ?? 'unknown', $path, $newPath);
        }
    }

    public function delete(string $path): void
    {
        $location = $this->pathPrefix->prefixPath($path);

        if (!$this->writer->delete($location)) {
            throw UnableToDeleteFile::atLocation($location, error_get_last()['message'] ?? '');
        }
    }

    public function deleteDirectory(string $dirname): void
    {
        $location = $this->pathPrefix->prefixPath($dirname);

        if (!$this->writer->rmdir($location)) {
            throw UnableToDeleteDirectory::atLocation($dirname, error_get_last()['message'] ?? '');
        }
    }

    public function createDirectory(string $dirname, Config $config): void
    {
        $location = $this->pathPrefix->prefixPath($dirname);
        $visibility = $config->get('visibility', Visibility::PUBLIC);

        if (!$this->writer->mkdir($location, $visibility)) {
            throw UnableToCreateDirectory::atLocation($dirname);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $location = $this->pathPrefix->prefixPath($path);
        if (!$this->writer->setVisibility($location, $visibility, 'file')) {
            throw UnableToSetVisibility::atLocation($path, error_get_last()['message'] ?? '');
        }
    }

    public function has(string $path): array|bool|null
    {
        $location = $this->pathPrefix->prefixPath($path);
        $metadata = $this->reader->getMetadata($location);

        return $metadata->isReadable() && !$metadata->isVirtual();
    }

    public function read(string $path): string
    {
        $location = $this->pathPrefix->prefixPath($path);
        $contents = $this->reader->read($location);

        if ($contents === false) {
            throw UnableToReadFile::fromLocation($path, error_get_last()['message'] ?? '');
        }

        return $contents;
    }

    public function listContents(string $directory = '', bool $recursive = false): array
    {
        $path = $this->pathPrefix->prefixPath($directory);
        $contents = $this->reader->listContents($path, $recursive);

        $result = [];
        foreach ($contents as $fileInfo) {
            $result[] = $this->fileInfoToFilesystemResult($fileInfo);
        }

        return $result;
    }

    public function readStream(string $path)
    {
        $location = $this->pathPrefix->prefixPath($path);
        $stream = $this->reader->readStream($location);

        if ($stream === false) {
            throw UnableToReadFile::fromLocation($path, error_get_last()['message'] ?? '');
        }

        return $stream;
    }


    public function fileSize(string $path): FileAttributes
    {
        $location = $this->pathPrefix->prefixPath($path);
        error_clear_last();

        if (is_file($location) && ($fileSize = @filesize($location)) !== false) {
            return new FileAttributes($path, $fileSize);
        }

        throw UnableToRetrieveMetadata::fileSize($path, error_get_last()['message'] ?? '');
    }

    public function mimeType(string $path): FileAttributes
    {
        $location = $this->pathPrefix->prefixPath($path);

        error_clear_last();

        if ( ! is_file($location)) {
            throw UnableToRetrieveMetadata::mimeType($location, 'No such file exists.');
        }

        $mimeType = $this->mimeTypeDetector->detectMimeTypeFromFile($location);

        if ($mimeType === null) {
            throw UnableToRetrieveMetadata::mimeType($path, error_get_last()['message'] ?? '');
        }

        return new FileAttributes($path, null, null, null, $mimeType);
    }

    public function lastModified(string $path): FileAttributes
    {
        $location = $this->pathPrefix->prefixPath($path);
        error_clear_last();
        $lastModified = @filemtime($location);

        if ($lastModified === false) {
            throw UnableToRetrieveMetadata::lastModified($path, error_get_last()['message'] ?? '');
        }

        return new FileAttributes($path, null, null, $lastModified);
    }

    public function visibility(string $path): FileAttributes
    {
        $details = $this->getMetadata($path);

        return new FileAttributes($path, null, $details['visibility']);
    }

    public function getMetadata($path)
    {
        $location = $this->pathPrefix->prefixPath($path);
        $metadata = $this->reader->getMetadata($location);
        if ($metadata->isVirtual()) {
            return false;
        }

        return $this->prepareMetadataResult($metadata);
    }

    protected function prepareMetadataResult(VirtualSplFileInfo $metadata): array
    {
        $result['visibility'] = $this->visibilityConverter->toVisibility((string)$metadata->getPerms(), $metadata->getType());
        $result['timestamp'] = $metadata->getMTime();
        $result['mimetype'] = $this->mimeTypeDetector->detectMimeType($metadata->getPathname(), '');

        return array_merge($this->fileInfoToFilesystemResult($metadata), $result);
    }

    protected function updatePathVisibility(string $path, Config $config): bool
    {
        $visibility = $config->get('visibility');
        if ($visibility) {
            $this->setVisibility($path, $visibility);
            return true;
        }

        return false;
    }

    public function removePathPrefix(string $path): string
    {
        $path = trim($this->pathPrefix->stripPrefix($path));

        if ($path === '') {
            $path = '/';
        }

        return $path;
    }

    protected function fileInfoToFilesystemResult(VirtualSplFileInfo $fileInfo): array
    {
        $item = $fileInfo->toArray();

        $item['path'] = $this->removePathPrefix($fileInfo->getPathname());
        $item['basename'] = $item['path'];
        $item['dirname'] = $this->removePathPrefix($fileInfo->getPath());
        $item['filename'] = pathinfo($fileInfo->getPathname(), \PATHINFO_FILENAME);
        $item['timestamp'] = $fileInfo->getMTime();
        unset($item['pathname']);

        return $item;
    }

    public function fileExists(string $path): bool
    {
        $location = $this->pathPrefix->prefixPath($path);

        return is_file($location);
    }

    public function directoryExists(string $path): bool
    {
        $location = $this->pathPrefix->prefixPath($path);

        return is_dir($location);
    }
}
