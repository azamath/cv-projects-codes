<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Services;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadsService implements UploadsServiceInterface
{

    public function __construct(private string $uploadsDir)
    {
    }

    public function moveUploadedFile(UploadedFile $uploadedFile, string $destPath = ''): File
    {
        $directory = $this->prepareDirectory($destPath);
        $fileName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        if ($ext = ($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->guessClientExtension())) {
            $ext = '.' . $ext;
        }

        $suffix = ''; $i = 0;
        while (file_exists("{$directory}/{$fileName}{$suffix}{$ext}")) {
            $i++;
            $suffix = "-{$i}";
        }

        $fileName = "{$fileName}{$suffix}{$ext}";

        return $uploadedFile->move($directory, $fileName);
    }

    protected function prepareDirectory(string $destPath): string
    {
        $directory = rtrim($this->uploadsDir, '/');
        $directory = rtrim($directory . '/' . $destPath, '/');
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $directory;
    }
}
