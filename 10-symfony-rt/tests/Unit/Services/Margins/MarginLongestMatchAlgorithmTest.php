<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Unit\Services\Margins;

use App\Entity\MarginSelf;
use App\Enum\EMarginValueType;
use App\Services\Margins\MarginLongestMatchAlgorithm;
use App\Services\Margins\NoMatchingMarginException;
use PHPUnit\Framework\TestCase;

class MarginLongestMatchAlgorithmTest extends TestCase
{

    public function testGetMatchingMarginsThrowsException()
    {
        $algorithm = new MarginLongestMatchAlgorithm();
        $testdata = $this->getTestDataMatrix();

        $this->expectException(\RuntimeException::class);
        $algorithm->getMatchingMargins(
            0,
            0,
            0,
            $testdata
        );
    }

    public function testFilterMarginsByMostMinimalWildcardCountEmpty()
    {
        $algorithm = new MarginLongestMatchAlgorithm();
        $result = $algorithm->filterMarginsByMostMinimalWildcardCount([]);
        $this->assertEquals([], $result);
    }

    public function testFilterMarginsByMostSpecificTupleFailsOnEmptMargins()
    {
        $algorithm = new MarginLongestMatchAlgorithm();
        $this->expectException(NoMatchingMarginException::class);
        $algorithm->filterMarginsByMostSpecificTuple([]);
    }

    public function testFilterMarginsByMostSpecificTupleFailsOnMultipleMarginMatches()
    {
        $algorithm = new MarginLongestMatchAlgorithm();
        $this->expectException(\RuntimeException::class);
        $algorithm->filterMarginsByMostSpecificTuple(
            $this->getTestDataMatrix()
        );
    }

    public function testValidateFailsWithInvalidResellerId()
    {
        $algorithm = new MarginLongestMatchAlgorithm();
        $validateMethod = $this->getProtectedMethodAsPublic(MarginLongestMatchAlgorithm::class, 'validate');
        $this->expectException(\InvalidArgumentException::class);
        $validateMethod->invokeArgs($algorithm, [1, 2, -1]);
    }

    public function testMarginFilter()
    {
        $algorithm = new MarginLongestMatchAlgorithm();
        $testdata = $this->getTestDataMatrix();

        $resultData = $algorithm->filterMargins(
            $testdata,
            1,
            1,
            1
        );

        $this->assertCount(3, $resultData);
        $this->assertContainsOnlyInstancesOf(MarginSelf::class, $resultData);
    }

    public function testFilterMarginsByMostMinimalWildcardCount()
    {
        $algorithm = new MarginLongestMatchAlgorithm();
        $testdata = $this->getTestDataMatrix();

        $filteredResultDataWildcards = $algorithm->filterMarginsByMostMinimalWildcardCount(
            $algorithm->filterMargins(
                $testdata,
                1,
                1,
                1
            )
        );

        $this->assertContainsOnlyInstancesOf(MarginSelf::class, $filteredResultDataWildcards);
        $this->assertCount(1, $filteredResultDataWildcards);
    }

    public function testFilterMarginsByMostSpecificTupleByExistingMargin()
    {
        $algorithm = new MarginLongestMatchAlgorithm();
        $testdata = $this->getTestDataMatrix();

        $list = array(
            $testdata[0], // 1, 1, 1, 1, 1
            $testdata[2], // null, null, null, 3, 1
            $testdata[3]  // 2, null, null, 4, 1
        );

        $filteredSpecificResult = $algorithm->filterMarginsByMostSpecificTuple($list);

        $this->assertInstanceOf(MarginSelf::class, $filteredSpecificResult);
        $this->assertEquals(1, $filteredSpecificResult->getVendorId());
        $this->assertEquals(1, $filteredSpecificResult->getOriginId());
        $this->assertEquals(1, $filteredSpecificResult->getResellerId());
        $this->assertEquals(1, $filteredSpecificResult->getMarginValue());
        $this->assertEquals(EMarginValueType::ABSOLUTE, $filteredSpecificResult->getMarginValueType());
    }

    public function testFilterMarginsByMostSpecificTupleByNonExistingMargin()
    {
        $algorithm = new MarginLongestMatchAlgorithm();
        $testdata = $this->getTestDataMatrix();

        $filteredSpecificResult = $algorithm->filterMarginsByMostSpecificTuple(
            $algorithm->filterMarginsByMostMinimalWildcardCount(
                $algorithm->filterMargins(
                    $testdata,
                    1,
                    1,
                    1
                )
            )
        );

        $this->assertInstanceOf(MarginSelf::class, $filteredSpecificResult);
        $this->assertEquals(1, $filteredSpecificResult->getVendorId());
        $this->assertEquals(1, $filteredSpecificResult->getOriginId());
        $this->assertEquals(1, $filteredSpecificResult->getResellerId());
        $this->assertEquals(1, $filteredSpecificResult->getMarginValue());
        $this->assertEquals(EMarginValueType::ABSOLUTE, $filteredSpecificResult->getMarginValueType());
    }

    public function testBatch()
    {
        $this->checkValidTupleParametersWithExpectedResult(1, 1, 1, array(1, 1, 1, 1, 1));
        $this->checkValidTupleParametersWithExpectedResult(42, 42, 1, array(null, null, 1, 2, 1));
        $this->checkValidTupleParametersWithExpectedResult(42, 42, 43, array(null, null, null, 3, 1));
        $this->checkValidTupleParametersWithExpectedResult(2, 2, 43, array(2, 2, null, 4, 1));
        $this->checkValidTupleParametersWithExpectedResult(2, 2, 2, array(2, 2, 2, 5, 1));
    }

    /**
     * @param int $resellerId
     * @param int $originId
     * @param int $vendorId
     * @param array $expectation
     */
    protected function checkValidTupleParametersWithExpectedResult(
        $resellerId,
        $originId,
        $vendorId,
        array $expectation
    ) {
        $algorithm = new MarginLongestMatchAlgorithm();
        $testdata = $this->getTestDataMatrix();

        $res = $algorithm->filterMarginsByMostSpecificTuple(
            $algorithm->filterMarginsByMostMinimalWildcardCount(
                $algorithm->filterMargins(
                    $testdata,
                    $vendorId,
                    $originId,
                    $resellerId
                )
            )
        );

        $this->assertInstanceOf(MarginSelf::class, $res);
        $this->assertEquals($expectation[0], $res->getResellerId());
        $this->assertEquals($expectation[1], $res->getOriginId());
        $this->assertEquals($expectation[2], $res->getVendorId());
        $this->assertEquals($expectation[3], $res->getMarginValue());
    }


    /**
     * Prepares the following test data matrix:
     *
     * case resellerId  originId  vendorId    marginValue marginValueType
     * a     1           1           1           1           1
     * b     *           *           1           2           1
     * c     *           *           *           3           1
     * d     2           2           *           4           1
     * e     2           2           2           5           1
     *
     * @return \App\Entity\MarginSelf[]
     */
    protected function getTestDataMatrix()
    {
        $data = array(
            array(1, 1, 1, 1, EMarginValueType::ABSOLUTE),
            array(null, null, 1, 2, EMarginValueType::ABSOLUTE),
            array(null, null, null, 3, EMarginValueType::ABSOLUTE),
            array(2, 2, null, 4, EMarginValueType::ABSOLUTE),
            array(2, 2, 2, 5, EMarginValueType::ABSOLUTE)
        );

        $list = array();
        foreach ($data as $d) {
            $bo = new \App\Entity\MarginSelf();
            $bo->setResellerId($d[0]);
            $bo->setOriginId($d[1]);
            $bo->setVendorId($d[2]);
            $bo->setMarginValue($d[3]);
            $bo->setMarginValueType($d[4]);

            $list[] = $bo;
        }

        return $list;
    }

    /**
     * Get protected method as public.
     * @param string $className
     * @param string $name
     * @return \ReflectionMethod
     */
    protected function getProtectedMethodAsPublic(string $className, string $name)
    {
        $class = new \ReflectionClass($className);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}
