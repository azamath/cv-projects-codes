<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Unit\Services;

use App\Services\UploadsService;
use App\Tests\Traits\HasVirtualFileSystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadsServiceTest extends TestCase
{
    use HasVirtualFileSystem;

    /**
     * @dataProvider moveUploadedFileDataProvider
     */
    public function testMoveUploadedFile(string $originalName, string $finalName, string $contents, string $mimeType = null)
    {
        // prepare test data
        $uploadedFile = new UploadedFile($this->letsHaveVfsFile('tempfile', $contents), $originalName, $mimeType, null, true);
        $uploadsDir = $this->letsHaveVfsDir('uploads');

        // actual call
        (new UploadsService($uploadsDir))->moveUploadedFile($uploadedFile);

        // assertions
        $this->assertFileExists("{$uploadsDir}/{$finalName}");
    }

    public function moveUploadedFileDataProvider(): array
    {
        return [
            [
                'originalName' => 'quote.csv',
                'finalName' => 'quote.csv',
                'contents' => 'col1,col2',
                'mimeType' => null,
            ],
            [
                'originalName' => 'quote',
                'finalName' => 'quote.csv',
                'contents' => 'col1,col2',
                'mimeType' => 'text/csv',
            ],
            [
                'originalName' => 'quote.xml',
                'finalName' => 'quote.xml',
                'contents' => '<?xml version="1.0" ?><foo></foo>',
                'mimeType' => null,
            ],
            [
                'originalName' => 'quote',
                'finalName' => 'quote.xml',
                'contents' => '<?xml version="1.0" ?><foo></foo>',
                'mimeType' => 'text/xml',
            ],
        ];
    }

    /**
     * @dataProvider moveUploadedFileExistsDataProvider
     */
    public function testMoveUploadedFileExists(string $originalName, string $finalName, array $existing)
    {
        // prepare test data
        $uploadedFile = new UploadedFile($this->letsHaveVfsFile('tempfile', 'content new'), $originalName, null, null, true);
        $uploadsDir = $this->letsHaveVfsDir('uploads');
        foreach ($existing as $_file => $_content) {
            $this->letsHaveVfsFile("uploads/{$_file}", $_content);
        }

        // actual call
        (new UploadsService($uploadsDir))->moveUploadedFile($uploadedFile);

        // assertions
        foreach ($existing as $file => $contents) {
            $this->assertEquals($contents, file_get_contents("{$uploadsDir}/{$file}"), "File: {$file}");
        }
        $this->assertFileExists("{$uploadsDir}/{$finalName}");
        $this->assertEquals('content new', file_get_contents("{$uploadsDir}/{$finalName}"));
    }

    public function moveUploadedFileExistsDataProvider(): array
    {
        return [
            [
                'originalName' => 'quote.csv',
                'finalName' => 'quote-1.csv',
                'existing' => [
                    'quote.csv' => 'content 0',
                ],
            ],
            [
                'originalName' => 'quote.csv',
                'finalName' => 'quote-3.csv',
                'existing' => [
                    'quote.csv' => 'content 0',
                    'quote-1.csv' => 'content 1',
                    'quote-2.csv' => 'content 2',
                ],
            ],
        ];
    }

    public function testMoveUploadedFileDestDir()
    {
        // prepare test data
        $uploadedFile = new UploadedFile($this->letsHaveVfsFile('tempfile', 'content new'), 'quote.csv', null, null, true);
        $uploadsDir = $this->letsHaveVfsDir('uploads');

        // actual call
        (new UploadsService($uploadsDir))->moveUploadedFile($uploadedFile, 'quotes');

        // assertions
        $this->assertFileExists("{$uploadsDir}/quotes/quote.csv");
        $this->assertEquals('content new', file_get_contents("{$uploadsDir}/quotes/quote.csv"));
    }
}
