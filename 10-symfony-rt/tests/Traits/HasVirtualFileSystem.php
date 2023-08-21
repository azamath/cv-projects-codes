<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Traits;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;

trait HasVirtualFileSystem
{
    protected function letsHaveVfsFile(string $fileName, string $contents = ''): string
    {
        return vfsStream::newFile($fileName)->setContent($contents)->at($this->getVfsRoot())->url();
    }

    protected function letsHaveVfsDir(string $dirName): string
    {
        return vfsStream::newDirectory($dirName)->at($this->getVfsRoot())->url();
    }

    protected function getVfsRootUrl()
    {
        return $this->getVfsRoot()->url();
    }

    protected function getVfsRoot(): vfsStreamDirectory
    {
        $root = vfsStreamWrapper::getRoot();
        return $root instanceof vfsStreamDirectory ? $root : vfsStream::setup();
    }
}
