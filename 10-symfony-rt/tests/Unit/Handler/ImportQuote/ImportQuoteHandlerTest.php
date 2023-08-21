<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Unit\Handler\ImportQuote;

use App\Dto\Collection\ExchangeRateCollection;
use App\Dto\ImportQuote\Context;
use App\Dto\ImportQuote\Result;
use App\Entity\Company;
use App\Entity\Currency;
use App\Entity\ImportLogQuote;
use App\Entity\MarginSelf;
use App\Entity\Quote;
use App\Entity\QuoteCompany;
use App\Entity\QuoteExchangeRate;
use App\Entity\QuoteProduct;
use App\Enum\ECompanyType;
use App\Enum\EImportLogResult;
use App\Enum\EMarginCalculation;
use App\Enum\EMarginType;
use App\Enum\EMarginValueType;
use App\Handler\ImportQuote\DataDenormalizer;
use App\Handler\ImportQuote\EntitiesValidator;
use App\Handler\ImportQuote\ExchangeRateHelper;
use App\Handler\ImportQuote\GenericMapper;
use App\Handler\ImportQuote\ImportQuoteHandler;
use App\Repository\CompanyRepository;
use App\Repository\CurrencyRepository;
use App\Services\MarginsService;
use App\Services\SystemInfoService;
use App\Tests\Traits\MocksDoctrine;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;


class ImportQuoteHandlerTest extends TestCase
{
    use MocksDoctrine;

    private $mockGenericMapper;
    private $mockCompanyRepository;
    private $mockCurrencyRepository;
    private $mockEntitiesValidator;
    private $mockSystemInfoService;
    private $mockMarginsService;

    public function testHandle()
    {
        // create handler itself
        $handler = $this->createImportQuoteHandler();

        // setup mock result
        $data = [new \App\Dto\ImportQuote\Quote(), new \App\Dto\ImportQuote\Quote(),];
        $mapperResults = [$this->createMapperResult(), $this->createMapperResult()];
        $this->setupMockResults(mapperResults: $mapperResults, data: $data, existingEndCustomers: [null, null]);

        // create input context
        $context = $this->createContext();

        // actual method call
        $results = $handler->handle($context);

        // assertions
        $this->assertCount(2, $results);
        $this->assertFalse($results->hasErrors());
        $this->assertEquals(EImportLogResult::SUCCESS, $context->getImportLogQuote()->getImportResult());
    }

    public function testExistingEndCustomer()
    {
        // create handler itself
        $handler = $this->createImportQuoteHandler();

        // setup mock result
        $mapperResult = $this->createMapperResult();
        $mapperResult->getEndCustomer()->setName('Existing');
        $existingEndCustomer = (new Company())
            ->setCompanyId(900)
            ->setName('Existing')
            ->setEmail('existing@email.com')
            ->setTelephone('999-999-999');
        $this->setupMockResults(mapperResults: [$mapperResult], existingEndCustomers: [$existingEndCustomer]);

        // actual method call
        $results = $handler->handle($this->createContext());

        // assertions
        $this->assertEquals(900, $results[0]->getEndCustomer()->getCompanyId());
        $this->assertEquals('Existing', $results[0]->getEndCustomer()->getName());
        $this->assertEquals('existing@email.com', $results[0]->getEndCustomer()->getEmail());
        $this->assertEquals('999-999-999', $results[0]->getEndCustomer()->getTelephone());
    }

    public function testPrepareQuote()
    {
        // create handler itself
        $handler = $this->createImportQuoteHandler();

        // setup mock result
        $mapperResult = $this->createMapperResult();
        $mapperResult->getQuote()->setOriginCurrencyCode(null);
        $this->setupMockResults(mapperResults: [$mapperResult], systemCurrencyCode: 'EUR');

        // actual method call
        $results = $handler->handle($this->createContext());

        // assertions
        $this->assertNotNull($results[0]->getQuote()->getExpirationDate());
        $this->assertEquals('EUR', $results[0]->getQuote()->getOriginCurrencyCode());
    }

    public function testPrepareExchangeRatesWhenEmpty()
    {
        // create handler itself
        $handler = $this->createImportQuoteHandler();

        // setup mock result
        $mapperResult = $this->createMapperResult();
        $mapperResult->setExchangeRates(new ExchangeRateCollection());
        $systemCurrencies = [
            (new Currency())->setCurrencyCode('EUR')->setConversionRate(1),
            (new Currency())->setCurrencyCode('SEK')->setConversionRate(10),
        ];
        $this->setupMockResults(mapperResults: [$mapperResult], systemCurrencies: $systemCurrencies);

        // actual method call
        $results = $handler->handle($this->createContext());

        // assertions
        $this->assertCount(count($systemCurrencies), $results[0]->getExchangeRates());
    }

    public function testPrepareExchangeRatesWhenMapped()
    {
        // create handler itself
        $handler = $this->createImportQuoteHandler();

        // setup mock result
        $mapperResult = $this->createMapperResult();
        $mapperResult->setExchangeRates(new ExchangeRateCollection([
            (new QuoteExchangeRate())->setCurrencyCode('SEK')->setConversionRate(11)
        ]));
        $systemCurrencies = [
            (new Currency())->setCurrencyCode('EUR')->setConversionRate(1),
            (new Currency())->setCurrencyCode('SEK')->setConversionRate(10),
        ];
        $this->setupMockResults(mapperResults: [$mapperResult], systemCurrencies: $systemCurrencies);

        // actual method call
        $results = $handler->handle($this->createContext());

        // assertions
        $this->assertCount(count($systemCurrencies), $results[0]->getExchangeRates());
        foreach ($results[0]->getExchangeRates() as $exchangeRate) {
            switch ($exchangeRate->getCurrencyCode()) {
                case 'EUR':
                    $this->assertEquals(1, $exchangeRate->getConversionRate());
                    break;
                case 'SEK':
                    $this->assertEquals(11, $exchangeRate->getConversionRate());
                    break;
            }
        }
    }

    public function testPricesForAnotherCurrency()
    {
        // create handler itself
        $handler = $this->createImportQuoteHandler();

        // setup mock result
        $mapperResult = $this->createMapperResult();
        $mapperResult->getQuote()->setOriginCurrencyCode('SEK');
        $mapperResult->setProducts(new ArrayCollection([
            (new QuoteProduct())->setPrice(110),
            (new QuoteProduct())->setPrice(220),
        ]));
        $mapperResult->setExchangeRates(new ExchangeRateCollection([
            (new QuoteExchangeRate())->setCurrencyCode('SEK')->setConversionRate(11)
        ]));
        $this->setupMockResults(mapperResults: [$mapperResult]);

        // actual method call
        $results = $handler->handle($this->createContext());

        // assertions
        /** @var Result $result1 */
        $result1 = $results[0];
        $this->assertEquals(10, $result1->getProducts()[0]->getPrice());
        $this->assertEquals(20, $result1->getProducts()[1]->getPrice());
    }

    public function testValidationErrors()
    {
        // create handler itself
        $handler = $this->createImportQuoteHandler();

        // setup mock result
        $errors = ['quote1 validation error'];
        $this->setupMockResults(
            mapperResults: [$this->createMapperResult()],
            validationErrors: [$errors],
        );

        // actual method call
        $context = $this->createContext();
        $results = $handler->handle($context);

        // assertions
        $this->assertCount(1, $results->getErrors());
        $this->assertEquals($errors[0], $results->getErrors()[0]);
        $this->assertEquals(EImportLogResult::FAIL, $context->getImportLogQuote()->getImportResult());
        $this->assertEquals(json_encode($errors), $context->getImportLogQuote()->getValidationResult());
    }

    protected function mockDenormalizer(): DataDenormalizer|\PHPUnit\Framework\MockObject\MockObject
    {
        if (!isset($this->mockDenormalizer)) {
            $this->mockDenormalizer = $this->createMock(DataDenormalizer::class);
        }

        return $this->mockDenormalizer;
    }

    protected function mockGenericMapper(): GenericMapper|\PHPUnit\Framework\MockObject\MockObject
    {
        if (!isset($this->mockGenericMapper)) {
            $this->mockGenericMapper = $this->createMock(GenericMapper::class);
        }

        return $this->mockGenericMapper;
    }

    protected function mockCompanyRepository(): \PHPUnit\Framework\MockObject\MockObject|CompanyRepository
    {
        if (!isset($this->mockCompanyRepository)) {
            $this->mockCompanyRepository = $this->createMock(CompanyRepository::class);
            $companySelf = (new Company())->setCompanyId(1);
            $this->mockCompanyRepository->expects($this->any())->method('findCompanySelf')->willReturn($companySelf);
        }

        return $this->mockCompanyRepository;
    }

    protected function mockCurrencyRepository(): \PHPUnit\Framework\MockObject\MockObject|CurrencyRepository
    {
        if (!isset($this->mockCurrencyRepository)) {
            $this->mockCurrencyRepository = $this->createMock(CurrencyRepository::class);
        }

        return $this->mockCurrencyRepository;
    }

    protected function mockValidator(): \PHPUnit\Framework\MockObject\MockObject|EntitiesValidator
    {
        if (!isset($this->mockEntitiesValidator)) {
            $this->mockEntitiesValidator = $this->createMock(EntitiesValidator::class);
        }

        return $this->mockEntitiesValidator;
    }

    protected function mockSystemInfo(): \PHPUnit\Framework\MockObject\MockObject|SystemInfoService
    {
        if (!isset($this->mockSystemInfoService)) {
            $this->mockSystemInfoService = $this->createMock(SystemInfoService::class);
        }

        return $this->mockSystemInfoService;
    }

    protected function mockMarginsService(): \PHPUnit\Framework\MockObject\MockObject|MarginsService
    {
        if (!isset($this->mockMarginsService)) {
            $this->mockMarginsService = $this->createMock(MarginsService::class);
        }

        return $this->mockMarginsService;
    }

    protected function createImportQuoteHandler(): ImportQuoteHandler
    {
        return new ImportQuoteHandler(
            $this->mockDenormalizer(),
            $this->mockGenericMapper(),
            $this->mockCompanyRepository(),
            $this->mockCurrencyRepository(),
            $this->createMock(ExchangeRateHelper::class),
            $this->mockValidator(),
            $this->mockSystemInfo(),
            $this->mockMarginsService(),
            $this->getMockDoctrine(),
        );
    }

    protected function setupMockResults(
        array $mapperResults,
        array $data = null,
        array $existingEndCustomers = [null],
        array $validationErrors = [[]],
        array $systemCurrencies = [],
        string $systemCurrencyCode = 'EUR',
    )
    {
        $this->mockDenormalizer()->expects($this->once())->method('denormalize')->willReturn($data ?? $this->createData());
        $this->mockGenericMapper()->expects($this->exactly(count($mapperResults)))->method('map')->willReturn(...$mapperResults);
        $this->mockCompanyRepository()->expects($this->exactly(count($existingEndCustomers)))->method('findOneBy')->willReturn(...$existingEndCustomers);
        $this->mockCurrencyRepository()->expects($this->any())->method('findAll')->willReturn($systemCurrencies);
        $this->mockValidator()->expects($this->any())->method('validate')->willReturn(...$validationErrors);
        $this->mockSystemInfo()->expects($this->any())->method('getCurrencyCode')->willReturn($systemCurrencyCode);
        $this->mockMarginsService()->expects($this->any())->method('getMargin')->willReturn($this->createMargin());
        $this->getMockObjectManager()->expects($this->any())->method('persist');
        $this->getMockObjectManager()->expects($this->any())->method('flush');
    }

    protected function createMapperResult(): Result
    {
        return (new Result())
            ->setEndCustomer(new Company())
            ->setQuote(new Quote())
            ->setQuoteCompany(new QuoteCompany())
            ->setProducts(new ArrayCollection([
                new QuoteProduct(),
                new QuoteProduct(),
            ]));
    }

    protected function createContext(): Context
    {
        $context = new Context();
        $context->setImportLogQuote(
            (new ImportLogQuote())
                ->setUserId(1)
                ->setVendorId(100)
                ->setResellerId(2)
                ->setFileName('test.xlsx')
        );
        $context->setVendor(
            (new Company())
                ->setCompanyId(100)
                ->setCompanyType(ECompanyType::VENDOR)
                ->setName('Test Vendor')
                ->setAlias('test_vendor')
        );
        $context->setReseller(
            (new Company())
                ->setCompanyId(2)
                ->setCompanyType(ECompanyType::RESELLER)
                ->setName('Test Reseller')
                ->setAlias('test_reseller')
        );
        $context->setUser(
            (new \App\Entity\User())
                ->setUserId(1)
                ->setUsername('tester')
                ->setEmail('tester@test.com')
        );
        $context->setData([]);

        return $context;
    }

    protected function createMargin(): MarginSelf
    {
        return (new MarginSelf())
            ->setMarginType(EMarginType::PRODUCT)
            ->setMarginValue(0.2)
            ->setMarginValueType(EMarginValueType::PERCENTAGE)
            ->setCalculationType(EMarginCalculation::MARGIN);
    }

    protected function createData(): array
    {
        return [
            new \App\Dto\ImportQuote\Quote()
        ];
    }
}
