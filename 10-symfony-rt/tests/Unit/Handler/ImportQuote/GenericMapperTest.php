<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Unit\Handler\ImportQuote;

class GenericMapperTest extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
{
    public function testMapping()
    {
        $rawData = [
            $this->createRawQuote()
        ];
        $data = $this->denormalizeData($rawData);

        $genericMapper = new \App\Handler\ImportQuote\GenericMapper();
        $result = $genericMapper->map($data[0]);
        $this->assertEquals('QUOTE01', $result->getQuote()->getQuoteNumber());
        $this->assertEquals('QUOTE01', $result->getQuote()->getOriginQuoteNumber());
        $this->assertEquals('QUOTE01', $result->getQuote()->getSimpleQuoteNumber());
        $this->assertEquals('EUR', $result->getQuote()->getOriginCurrencyCode());
        $this->assertCount(1, $result->getProducts());
        $this->assertEquals('SKU1', $result->getProducts()[0]->getSku());
        $this->assertEquals('Product 1', $result->getProducts()[0]->getName());
        $this->assertEquals(100, $result->getProducts()[0]->getPrice());
        $this->assertEquals(100, $result->getProducts()[0]->getSinglePrice());
        $this->assertEquals(2, $result->getProducts()[0]->getQuantity());
        $this->assertSame([], $result->getProducts()[0]->getRawData());
    }

    public function testProductRawData()
    {
        $productRawData = ['key1' => 'val1', 'key2' => [1, 2, 3]];
        $data = $this->denormalizeData([
            $this->createRawQuote([
                $this->createRawProduct() + ['rawData' => $productRawData]
            ])
        ]);

        $genericMapper = new \App\Handler\ImportQuote\GenericMapper();
        $result = $genericMapper->map($data[0]);
        $this->assertSame($productRawData, $result->getProducts()[0]->getRawData());
    }

    /**
     * @dataProvider dataProviderForPriceCalculation
     * @param float $price
     * @param float $qty
     * @param float $totalPrice
     */
    public function testProductWithoutUnitPrice(float $price, float $qty, float $totalPrice)
    {
        $rawData = [
            $this->createRawQuote(
                products: [
                    $this->createRawProduct(price: null, qty: (string)$qty, totalPrice: (string)$totalPrice)
                ],
            )
        ];
        $data = $this->denormalizeData($rawData);

        $genericMapper = new \App\Handler\ImportQuote\GenericMapper();
        $result = $genericMapper->map($data[0]);
        $this->assertEquals($qty, $result->getProducts()[0]->getQuantity());
        $this->assertEquals($price, $result->getProducts()[0]->getSinglePrice());
    }

    /**
     * @dataProvider dataProviderForPriceCalculation
     * @param float $price
     * @param float $qty
     * @param float $totalPrice
     */
    public function testProductWithoutQuantity(float $price, float $qty, float $totalPrice)
    {
        $rawData = [
            $this->createRawQuote(
                products: [
                    $this->createRawProduct(price: (string)$price, qty: null, totalPrice: (string)$totalPrice)
                ],
            )
        ];
        $data = $this->denormalizeData($rawData);

        $genericMapper = new \App\Handler\ImportQuote\GenericMapper();
        $result = $genericMapper->map($data[0]);
        $this->assertEquals($qty, $result->getProducts()[0]->getQuantity());
        $this->assertEquals($price, $result->getProducts()[0]->getSinglePrice());
    }

    protected function dataProviderForPriceCalculation(): array
    {
        return [
          [
              'price' => 50,
              'qty' => 2,
              'totalPrice' => 100,
          ],
          [
              'price' => 100 / 3,
              'qty' => 3,
              'totalPrice' => 100,
          ],
          [
              'price' => 100 / 6,
              'qty' => 6,
              'totalPrice' => 100,
          ],
        ];
    }

    /**
     * @param mixed $rawData
     * @return \App\Dto\ImportQuote\Quote[]
     */
    protected function denormalizeData(mixed $rawData): array
    {
        $dataDenormalizer = $this->getContainer()->get(\App\Handler\ImportQuote\DataDenormalizer::class);
        return $dataDenormalizer->denormalize($rawData);
    }

    protected function createRawQuote($products = null): array
    {
        $products = $products ?? [
            $this->createRawProduct()
        ];
        return [
            'quoteNumber' => 'QUOTE01',
            'currencyCode' => 'EUR',
            'endCustomer' => $this->createRawEndCustomer(),
            'products' => $products,
        ];
    }

    protected function createRawEndCustomer(): array
    {
        return [
            'name' => 'Some Name',
        ];
    }

    protected function createRawProduct($unique = '1', $price = '100', $qty = '2', $totalPrice = null): array
    {
        return [
            'sku' => 'SKU' . $unique,
            'name' => 'Product ' . $unique,
            'price' => $price,
            'quantity' => $qty,
            'totalPrice' => $totalPrice,
        ];
    }
}
