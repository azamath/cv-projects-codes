<?php

namespace App\Controller;

use App\Dto\ImportQuote\Context;
use App\Entity\Quote;
use App\Handler\DuplicateQuoteHandler;
use App\Handler\ImportQuote\ImportQuoteHandler;
use App\Handler\ImportQuote\ImportServiceUnavailableException;
use App\Handler\ImportQuote\UnsupportedFileTypeException;
use App\Handler\ImportQuote\UploadQuoteHandler;
use App\Repository\CompanyRepository;
use App\Repository\ImportLogQuoteRepository;
use App\Repository\QuoteRepository;
use App\Repository\UserRepository;
use App\Traits\HasLogger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Annotation\Route;

class QuotesController extends AbstractController implements LoggerAwareInterface
{
    use HasLogger;

    #[Route('/quotes/upload', name: 'quotes.upload', methods: ['POST'])]
    public function upload(Request $request, UploadQuoteHandler $uploadQuoteHandler): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('file');
        $vendorId = (int)$request->request->getDigits('vendorId');
        $resellerId = (int)$request->request->getDigits('resellerId');

        try {
            $result = $uploadQuoteHandler->handle($uploadedFile, $vendorId, $resellerId);
        } catch (UnsupportedFileTypeException $e) {
            return $this->json([
                'type' => 'unsupported_file_type',
                'message' => $e->getMessage(),
            ], 422);
        } catch (ImportServiceUnavailableException $e) {
            throw new ServiceUnavailableHttpException(null, $e->getMessage());
        }

        return $this->json(['message' => 'Uploaded'] + $result);
    }

    #[Route('/quotes/import-callback/{importLogId}', name: 'quotes.import-callback', methods: ['POST'])]
    public function importCallback(
        $importLogId,
        Request $request,
        ImportQuoteHandler $importQuoteHandler,
        ImportLogQuoteRepository $importLogQuoteRepository,
        CompanyRepository $companyRepository,
        UserRepository $userRepository,
        LoggerInterface $logger,
    ): Response
    {
        $logger->debug('ImportModule callback payload: ' . $request->getContent());

        $importLogQuote = $importLogQuoteRepository->find($importLogId);
        if (!$importLogQuote) {
            $logger->warning('ImportLogQuote not found with ID: ' . $importLogId);
            return $this->json([
                'status' => 'error',
                'message' => 'ImportLogQuote not found with ID: ' . $importLogId,
            ]);
        }

        $vendor = $companyRepository->find($importLogQuote->getVendorId());
        $reseller = $companyRepository->find($importLogQuote->getResellerId());
        $user = $userRepository->find($importLogQuote->getUserId());
        $payload = $request->request->all();

        // Create Context DTO for handler
        $context = (new Context())
            ->setImportLogQuote($importLogQuote)
            ->setData($payload['data'] ?? null)
            ->setRawData($payload['rawData'] ?? null)
            ->setVendor($vendor)
            ->setReseller($reseller)
            ->setUser($user);

        $results = $importQuoteHandler->handle($context);

        if ($results->hasErrors()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Entities validation failed',
                'errors' => $results->getErrors(),
            ]);
        }

        return $this->json(['status' => 'success']);
    }

    #[Route('/quotes/{quoteId<\d+>}/duplicate', name: 'quotes.duplicate', methods: ['POST'])]
    public function duplicate(int $quoteId, Request $request, DuplicateQuoteHandler $duplicateQuoteHandler): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $duplicateQuoteHandler->handle($quoteId, $request->request->all());

        return $this->json(['message' => 'Duplicate quote was created']);
    }

    #[Route('/quotes/bulk-delete', name: 'quotes.bulk-delete', methods: ['POST'])]
    public function bulkDelete(Request $request, EntityManagerInterface $manager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /** @var QuoteRepository $quoteRepository */
        $quoteRepository = $manager->getRepository(Quote::class);
        $quotes = $quoteRepository->findBy(['quoteId' => $request->get('quoteId')]);
        foreach ($quotes as $quote) {
            $quoteRepository->delete($quote);
        }
        $manager->flush();

        return $this->json(['message' => sprintf("%d quotes have been deleted", count($quotes))]);
    }
}
