<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Services;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface UploadsServiceInterface
{
    /**
     * Moves an uploaded file to configured location for this service.
     * Renames file in case destination exists by adding unique suffix.
     *
     * @param UploadedFile $uploadedFile
     * @param string $destPath
     * @return File
     */
    public function moveUploadedFile(UploadedFile $uploadedFile, string $destPath = ''): File;
}
