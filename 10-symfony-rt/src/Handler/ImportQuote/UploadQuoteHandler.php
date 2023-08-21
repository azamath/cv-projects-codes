<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Handler\ImportQuote;

use App\Entity\ImportLogQuote;
use App\Entity\User;
use App\Enum\EImportLogResult;
use App\Repository\CompanyRepository;
use App\Services\ImportService;
use App\Services\UploadsServiceInterface;
use App\Traits\HasLogger;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class UploadQuoteHandler implements LoggerAwareInterface
{
    use HasLogger;

    public function __construct(
        private CompanyRepository $companyRepository,
        private UploadsServiceInterface $uploadsService,
        private ImportService $importService,
        private Security $security,
        private UrlGeneratorInterface $router,
        private ManagerRegistry $doctrine,
    )
    {
    }

    /**
     * @throws UnsupportedFileTypeException
     * @throws ImportServiceUnavailableException
     */
    public function handle(UploadedFile $uploadedFile, int $vendorId, int $resellerId): array
    {
        // Resolve vendor code
        $vendor = $this->companyRepository->find($vendorId);
        $vendorCode = $vendor->getAlias();

        // move file to permanent storage
        $file = $this->uploadsService->moveUploadedFile($uploadedFile, 'quotes');
        $fileType = $file->getExtension();

        // Resolve pipelineId
        try {
            $res = $this->importService->getPipelineId($fileType, $vendorCode);
        } catch (Exception $e) {
            (new Filesystem())->remove($file);
            throw new ImportServiceUnavailableException($e->getMessage());
        }

        if ('success' !== $res['status']) {
            (new Filesystem())->remove($file);
            $this->logInfo("Import Service doesn't support import for vendor '{$vendorCode}' and file type '{$fileType}'");
            throw new UnsupportedFileTypeException('This file type is not supported yet.');
        }

        $pipelineId = (string)$res['pipeline_id'];

        /** @var User $user */
        $user = $this->security->getUser();

        // Create import log record
        $importLogQuote = $this->createImportLog($user, $vendorId, $file, $resellerId);

        // Send a file to the Import Service
        try {
            $callbackUrl = $this->getCallbackUrl($importLogQuote->getImportLogId());
            $this->importService->upload($file->getPathname(), $pipelineId, $callbackUrl);
            $this->logInfo("A file has been uploaded to the Import Service: {$file->getFilename()}");
        } catch (Exception $e) {
            $this->doctrine->getManager()->remove($importLogQuote);
            (new Filesystem())->remove($file);
            throw new ServiceUnavailableHttpException(null, $e->getMessage());
        }

        $this->doctrine->getManager()->flush();

        return compact(
            'vendorCode',
            'fileType',
            'pipelineId',
            'importLogQuote',
            'callbackUrl',
        );
    }

    protected function createImportLog(User $user, int $vendorId, File $file, int $resellerId): ImportLogQuote
    {
        $importLogQuote = new ImportLogQuote();
        $importLogQuote->setUserId($user->getUserId());
        $importLogQuote->setVendorId($vendorId);
        $importLogQuote->setFilename($file->getFilename());
        $importLogQuote->setImportDate(new DateTime());
        $importLogQuote->setImportResult(EImportLogResult::PENDING);
        $importLogQuote->setResellerId($resellerId);

        $this->doctrine->getManager()->persist($importLogQuote);
        $this->logInfo("{$importLogQuote}: created");

        return $importLogQuote;
    }

    protected function getCallbackUrl($importLogId): string
    {
        return $this->router->generate('quotes.import-callback', compact('importLogId'), UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
