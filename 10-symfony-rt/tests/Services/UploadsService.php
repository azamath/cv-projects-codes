<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Services;

use org\bovigo\vfs\vfsStream;

class UploadsService extends \App\Services\UploadsService
{
    use \App\Tests\Traits\HasVirtualFileSystem;

    public function __construct(string $uploadsDir)
    {
        $uploadsDir = $this->getVfsDirectory($uploadsDir);
        parent::__construct($uploadsDir);
    }

    protected function getVfsDirectory(string $uploadsDir): string
    {
        $dirStructure = [];
        $last = &$dirStructure;
        foreach (explode('/', trim($uploadsDir, '/')) as $dirName) {
            $last[$dirName] = [];
            $last = &$last[$dirName];
        }
        $root = vfsStream::create($dirStructure, $this->getVfsRoot());

        return $root->url() . $uploadsDir;
    }
}
