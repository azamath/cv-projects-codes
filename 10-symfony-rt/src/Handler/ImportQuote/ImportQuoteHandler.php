<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Handler\ImportQuote;

use App\Dto\ImportQuote\Context;
use App\Dto\ImportQuote\Result;
use App\Dto\ImportQuote\Results;
use App\Entity\Currency;
use App\Entity\QuoteExchangeRate;
use App\Enum\ECompanyType;
use App\Enum\EImportLogResult;
use App\Repository\CompanyRepository;
use App\Repository\CurrencyRepository;
use App\Services\MarginsService;
use App\Services\SystemInfoService;
use App\Traits\HasLogger;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerAwareInterface;

/**
 * Handler is responsible for converting input data into all Quote related BO.
 * It uses mappers for filling BO's data from input data.
 */
class ImportQuoteHandler implements LoggerAwareInterface
{
    use HasLogger;

    private const DB_CURRENCY_CODE = 'EUR';

    /**
     * @var Currency[]
     */
    private $systemCurrencies;

    public function __construct(
        private DataDenormalizer $denormalizer,
        private GenericMapper $genericMapper,
        private CompanyRepository $companyRepository,
        private CurrencyRepository $currencyRepository,
        private ExchangeRateHelper $exchangeRateHelper,
        private EntitiesValidator $entitiesValidator,
        private SystemInfoService $systemInfo,
        private MarginsService $marginsService,
        private ManagerRegistry $doctrine,
    )
    {
    }

    /**
     * Handles import quote
     *
     * @param Context $context
     * @return Results
     */
    public function handle(Context $context): Results
    {
        $results = $this->process($context);

        if ($results->hasErrors()) {
            $this->logWarning('Import quote validation errors:', $results->getErrors());

            // Set import log status as FAILED
            $context->getImportLogQuote()->setImportResult(EImportLogResult::FAIL);
            $context->getImportLogQuote()->setValidationResult(json_encode($results->getErrors()));
        }
        else {
            foreach ($results as $result) {
                $this->persistInDB($result);
            }

            $this->logInfo(sprintf('Imported new quotes: %d; Vendor code: %s', count($results), $context->getVendor()->getAlias()));

            // Set import log status as SUCCESS
            $context->getImportLogQuote()->setImportResult(EImportLogResult::SUCCESS);
            $context->getImportLogQuote()->setValidationResult(json_encode([]));
        }

        $this->doctrine->getManager()->flush();

        return $results;
    }

    protected function process(Context $context): Results
    {
        $results = new Results();

        try {
            $data = $this->denormalizer->denormalize($context->getData());
        } catch (\Symfony\Component\Serializer\Exception\ExceptionInterface $e) {
            $results->addError('Data parsing exception: ' . $e->getMessage());
            return $results;
        }

        if (0 === count($data)) {
            $results->addError('Number of quotes received: 0');
            return $results;
        }

        foreach ($data as $i => $datum) {
            try {
                $result = $this->genericMapper->map($datum);
                $this->prepareEndCustomer($result);
                $this->prepareQuote($context, $result);
                $this->prepareExchangeRates($result);

                if ($this->validateEntities($result, $i)) {
                    $this->preparePricesToDbCurrency($result);
                    $this->prepareMargins($context, $result);
                    $this->prepareRelations($result);
                }

                $results->add($result);
            } catch (\Throwable $e) {
                $results->addError("data[$i]: " . $e->getMessage());
                continue;
            }
        }

        return $results;
    }

    protected function prepareEndCustomer(Result $result): void
    {
        $endCustomerExisting = $this->companyRepository->findOneBy([
            'companyType' => ECompanyType::END_CUSTOMER->value,
            'name' => $result->getEndCustomer()->getName(),
        ]);
        if ($endCustomerExisting) {
            $endCustomer = $result->getEndCustomer();
            if ($endCustomer->getEmail()) {
                $endCustomerExisting->setEmail($endCustomer->getEmail());
            }
            if ($endCustomer->getTelephone()) {
                $endCustomerExisting->setTelephone($endCustomer->getTelephone());
            }
            if ($endCustomer->getFax()) {
                $endCustomerExisting->setFax($endCustomer->getFax());
            }
            if ($endCustomer->getWeb()) {
                $endCustomerExisting->setWeb($endCustomer->getWeb());
            }

            $result->setEndCustomer($endCustomerExisting);
        }

        $result->getEndCustomer()->setCompanyType(ECompanyType::END_CUSTOMER);
    }

    protected function prepareQuote(Context $context, Result $result): void
    {
        $importLogQuote = $context->getImportLogQuote();
        $companySelf = $this->companyRepository->findCompanySelf();
        $quote = $result->getQuote();
        $quote->setVendorId($importLogQuote->getVendorId());
        $quote->setResellerId($importLogQuote->getResellerId());
        $quote->setOriginCompanyId($companySelf->getCompanyId());
        $quote->setConfirmed(true);
        $quote->setFilename($importLogQuote->getFileName());
        $quote->setName($importLogQuote->getFileName());
        $quote->setProductCnt(count($result->getProducts()));
        $quote->setVendor($context->getVendor());
        $quote->setReseller($context->getReseller());
        $quote->setOriginCompany($companySelf);
        $quote->setCreatedUser($context->getUser());
        $quote->setModifiedUser($context->getUser());

        if ('' == $quote->getVendorName()) {
            $quote->setVendorName($context->getVendor()->getName());
        }

        if (null == $quote->getExpirationDate()) {
            $quote->setExpirationDate(new \DateTime('+30 days'));
        }

        if (null == $quote->getOriginCurrencyCode()) {
            $quote->setOriginCurrencyCode($this->systemInfo->getCurrencyCode());
        }
    }

    protected function prepareExchangeRates(Result $result): void
    {
        $this->exchangeRateHelper->alignRatesToBaseCurrency($result->getExchangeRates(), static::DB_CURRENCY_CODE);

        foreach ($this->getSystemCurrencies() as $systemCurrency) {
            // skip if a currency is already there
            if ($result->getExchangeRates()->containsCode($systemCurrency->getCurrencyCode())) {
                continue;
            }

            // add system currency as quote exchange rate
            $result->getExchangeRates()->add(
                (new QuoteExchangeRate())
                    ->setCurrencyCode($systemCurrency->getCurrencyCode())
                    ->setConversionRate($systemCurrency->getConversionRate())
            );
        }
    }

    protected function validateEntities(Result $result, int|string $i): bool
    {
        $errors = $this->entitiesValidator->validate($result, "data[$i]");
        if (count($errors)) {
            $result->setValidationErrors($errors);
            return false;
        }

        return true;
    }

    protected function preparePricesToDbCurrency(Result $result): void
    {
        $currencyCode = $result->getQuote()->getOriginCurrencyCode();
        if ($currencyCode === self::DB_CURRENCY_CODE) {
            return;
        }

        /** @var QuoteExchangeRate $exchangeRate */
        $exchangeRate = $result->getExchangeRates()->getByCode($currencyCode);
        if (null === $exchangeRate) {
            throw new \Exception("Exchange rate for '$currencyCode' not found");
        }

        $conversionRate = $exchangeRate->getRate();

        foreach ($result->getProducts() as $product) {
            if ($product->getPrice()) {
                $product->setPrice($product->getPrice() / $conversionRate);
            }
            if ($product->getSinglePrice()) {
                $product->setSinglePrice($product->getSinglePrice() / $conversionRate);
            }
            if ($product->getAnnualList()) {
                $product->setAnnualList($product->getAnnualList() / $conversionRate);
            }
            if ($product->getExtendedPrice()) {
                $product->setExtendedPrice($product->getExtendedPrice() / $conversionRate);
            }
        }
    }

    /**
     * @return Currency[]
     */
    protected function getSystemCurrencies(): array
    {
        if (!isset($this->systemCurrencies)) {
            $this->systemCurrencies = $this->currencyRepository->findAll();
        }

        return $this->systemCurrencies;
    }

    protected function prepareMargins(Context $context, Result $result): void
    {
        $margin = $this->marginsService->getMargin(
            $context->getVendor()->getCompanyId(),
            0,
            $context->getImportLogQuote()->getResellerId(),
        );

        foreach ($result->getProducts() as $product) {
            $product->setMarginSelfValue($margin->getMarginValue());
            $product->setMarginSelfValueType($margin->getMarginValueType());
            $product->setMarginSelfCalculationType($margin->getCalculationType());
        }
    }

    protected function prepareRelations(Result $result): void
    {
        $quote = $result->getQuote();
        $quote->setEndCustomer($result->getEndCustomer());
        $quote->setQuoteCompany($result->getQuoteCompany());
        foreach ($result->getProducts() as $product) {
            $quote->addQuoteProduct($product);
        }
        foreach ($result->getExchangeRates() as $exchangeRate) {
            $quote->addExchangeRate($exchangeRate);
        }
    }

    protected function persistInDB(Result $result): void
    {
        $manager = $this->doctrine->getManager();
        $manager->persist($result->getEndCustomer());
        $manager->persist($result->getQuote());
        $manager->persist($result->getQuoteCompany());
        foreach ($result->getProducts() as $quoteProduct) {
            $manager->persist($quoteProduct);
        }
        foreach ($result->getExchangeRates() as $exchangeRate) {
            $manager->persist($exchangeRate);
        }
    }
}
