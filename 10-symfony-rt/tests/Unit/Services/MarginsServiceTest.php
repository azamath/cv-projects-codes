<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Unit\Services;

use App\Entity\MarginCache;
use App\Entity\MarginSelf;
use App\Enum\EMarginCalculation;
use App\Enum\EMarginType;
use App\Enum\EMarginValueType;
use App\Repository\MarginCacheRepository;
use App\Repository\MarginSelfRepository;
use App\Services\Margins\MarginLongestMatchAlgorithm;
use App\Services\Margins\NoMatchingMarginException;
use App\Services\MarginsService;
use App\Tests\Traits\MocksDoctrine;
use PHPUnit\Framework\TestCase;

class MarginsServiceTest extends TestCase
{
    use MocksDoctrine;

    public function testGetMargin()
    {
        // mocks and service instance
        $mockMarginSelfRepository = $this->createMock(MarginSelfRepository::class);
        $mockMarginCacheRepository = $this->createMock(MarginCacheRepository::class);
        $mockMarginAlgorithm = $this->createMock(MarginLongestMatchAlgorithm::class);
        $service = new MarginsService($mockMarginSelfRepository, $mockMarginCacheRepository, $mockMarginAlgorithm, $this->getMockDoctrine());

        // setup mocks
        $mockMarginCacheRepository->method('findOneByHash')->willReturn(null);
        $margins = [
            (new MarginSelf())
                ->setMarginValue(0.2)
                ->setMarginType(EMarginType::PRODUCT)
                ->setMarginValueType(EMarginValueType::PERCENTAGE)
                ->setCalculationType(EMarginCalculation::MARGIN)
            ,
            (new MarginSelf())
                ->setMarginValue(0.3)
                ->setMarginType(EMarginType::PRODUCT)
                ->setMarginValueType(EMarginValueType::PERCENTAGE)
                ->setCalculationType(EMarginCalculation::MARGIN)
            ,
        ];
        $mockMarginSelfRepository->method('findByType')->willReturn($margins);
        $mockMarginAlgorithm->method('getMatchingMargins')->willReturn($margins[0]);

        $result = $service->getMargin(1, 0, 2);

        // assertions
        $this->assertSame($margins[0], $result);
    }

    public function testGetMarginCacheHit()
    {
        // mocks and service instance
        $mockMarginSelfRepository = $this->createMock(MarginSelfRepository::class);
        $mockMarginCacheRepository = $this->createMock(MarginCacheRepository::class);
        $mockMarginAlgorithm = $this->createMock(MarginLongestMatchAlgorithm::class);
        $service = new MarginsService($mockMarginSelfRepository, $mockMarginCacheRepository, $mockMarginAlgorithm, $this->getMockDoctrine());

        // setup mocks
        $cache = (new MarginCache())
            ->setMarginValue(0.2)
            ->setMarginValueType(EMarginValueType::PERCENTAGE)
            ->setCalculationType(EMarginCalculation::MARGIN);
        $mockMarginCacheRepository->method('findOneByHash')->willReturn($cache);
        $mockMarginSelfRepository->expects(self::never())->method('findByType');
        $mockMarginAlgorithm->expects(self::never())->method('getMatchingMargins');

        $result = $service->getMargin(1, 0, 2);

        // assertions
        $this->assertEquals($cache->getMarginValue(), $result->getMarginValue());
        $this->assertEquals($cache->getMarginValueType(), $result->getMarginValueType());
        $this->assertEquals($cache->getCalculationType(), $result->getCalculationType());
    }

    public function testGetMarginReturnsFallback()
    {
        // mocks and service instance
        $mockMarginSelfRepository = $this->createMock(MarginSelfRepository::class);
        $mockMarginCacheRepository = $this->createMock(MarginCacheRepository::class);
        $mockMarginAlgorithm = $this->createMock(MarginLongestMatchAlgorithm::class);
        $service = new MarginsService($mockMarginSelfRepository, $mockMarginCacheRepository, $mockMarginAlgorithm, $this->getMockDoctrine());

        // setup mocks
        $mockMarginCacheRepository->method('findOneByHash')->willReturn(null);
        $mockMarginSelfRepository->method('findByType')->willReturn([]);
        $mockMarginAlgorithm->method('getMatchingMargins')->willThrowException(new NoMatchingMarginException());
        $service->setDefaultMarginValue(0.3);
        $service->setDefaultMarginValueType(EMarginValueType::ABSOLUTE);
        $service->setDefaultCalculationType(EMarginCalculation::SURCHARGE);

        $result = $service->getMargin(1, 0, 2);

        // assertions
        $this->assertEquals(EMarginType::PRODUCT, $result->getMarginType());
        $this->assertEquals(1, $result->getVendorId());
        $this->assertEquals(0, $result->getOriginId());
        $this->assertEquals(2, $result->getResellerId());
        $this->assertEquals(0.3, $result->getMarginValue());
        $this->assertEquals(EMarginValueType::ABSOLUTE, $result->getMarginValueType());
        $this->assertEquals(EMarginCalculation::SURCHARGE, $result->getCalculationType());
    }

}
