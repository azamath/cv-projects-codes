<?php

namespace App\Tests\Feature\PasswordReset;

use App\Factory\UserFactory;
use App\Handler\PasswordReset\PasswordResetCodeHandler;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class PasswordResetApiTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function testForgotReset(): void
    {
        $email = UserFactory::new()->create()->getEmail();

        // non existing email
        $this->client->jsonRequest('POST', '/forgot-password', ['email' => 'wrong@test.loc']);
        $this->assertResponseStatusCodeSame(400);

        // successful
        $this->client->jsonRequest('POST', '/forgot-password', compact('email'));
        $this->assertResponseIsSuccessful();
        $this->assertEmailCount(1);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('expires_in', $response);

        // get last created reset code
        $code = PasswordResetCodeHandler::$codeForTesting;
        $emailContents = $this->getMailerMessage()->toString();
        $this->assertStringContainsString($code->getCode(), $emailContents);

        // bad requests
        $this->client->jsonRequest('POST', '/reset-password', [
            'email' => $email,
            'code' => 'WRONG',
            'password' => 'new-password',
        ]);
        $this->assertResponseStatusCodeSame(400);

        // successful
        $this->client->jsonRequest('POST', '/reset-password', [
            'email' => $email,
            'code' => $code->getCode(),
            'password' => 'new-password',
        ]);
        $this->assertResponseIsSuccessful();
    }

    protected function extractCodeFromMessage(?RawMessage $message): string
    {
        if ($message instanceof Email) {
            $messageBody = $message->getTextBody();
        } else {
            $messageBody = $message->toString();
        }
        $codeRegx = '/Code:\s+([\w\-]+)/';
        $this->assertMatchesRegularExpression($codeRegx, $messageBody);
        preg_match($codeRegx, $messageBody, $codeMatches);

        return $codeMatches[1];
    }
}
