<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Feature;

use App\Factory\QuoteFactory;
use App\Factory\UserFactory;
use App\Tests\Story\CurrenciesStory;
use App\Tests\Traits\ContainerHelpers;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

class DuplicateQuoteTest extends WebTestCase
{
    use ContainerHelpers;
    use Factories;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function testNotFound()
    {
        $user = UserFactory::createOne();
        $this->getEntityManager()->clear();

        $this->client->loginUser($user->object());
        $this->client->jsonRequest('POST', sprintf("/quotes/%d/duplicate", 1), [
            'expirationDate' => date_create('+60 days')->format('Y-m-d'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testNotAllowed()
    {
        CurrenciesStory::load();
        $user = UserFactory::first('userId');
        $quote = QuoteFactory::new()->full()->withBaseSinging()->create();
        $this->getEntityManager()->clear();

        $this->client->loginUser($user->object());
        $this->client->jsonRequest('POST', sprintf("/quotes/%d/duplicate", $quote->getQuoteId()), [
            'expirationDate' => date_create('+60 days')->format('Y-m-d'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testDuplicates()
    {
        CurrenciesStory::load();
        $user = UserFactory::first('userId');
        $quote = QuoteFactory::new()->full()->create();
        $this->getEntityManager()->clear();

        $this->client->loginUser($user->object());
        $newExpiration = date_create('+60 days')->format('Y-m-d');
        $this->client->jsonRequest('POST', sprintf("/quotes/%d/duplicate", $quote->getQuoteId()), [
            'expirationDate' => $newExpiration,
        ]);
        $this->assertResponseIsSuccessful();

        $quotes = $this->getQuoteRepository()->findBy(['quoteNumber' => $quote->getQuoteNumber()], ['quoteId' => 'ASC']);
        $this->assertCount(2, $quotes);
        $this->assertNotEquals(date_create($newExpiration), $quotes[0]->getExpirationDate());
        $this->assertEquals(date_create($newExpiration), $quotes[1]->getExpirationDate());
    }
}
