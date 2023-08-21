<?php

namespace App\Tests\Feature\SyncQuoteState;

use App\Controller\Internal\SigningsController;
use App\Enum\ESigningState;
use App\Factory\SigningFactory;
use App\Tests\Traits\ContainerHelpers;
use App\Tests\Traits\MocksDoctrine;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class StateUpdateTest extends KernelTestCase
{
    use MocksDoctrine;
    use ContainerHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
    }

    /**
     * @dataProvider dataProviderRejection
     */
    public function testStateUpdateRejection($payload)
    {
        $this->expectException(UnprocessableEntityHttpException::class);
        $this->getController()->stateUpdate(
            1,
            $this->createStateUpdateRequest($payload),
        );
    }

    public function dataProviderRejection(): array
    {
        return [
            [$payload = ''],
            [$payload = []],
            [$payload = ['state' => null]],
            [$payload = ['state' => '1']],
            [$payload = ['state' => 999]],
        ];
    }

    public function testStateUpdateNotFoundException()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->getController()->stateUpdate(
            1,
            $this->createStateUpdateRequest(['state' => 1]),
        );
    }

    public function testStateUpdate(): void
    {
        $signing = SigningFactory::new()
            ->forSomeQuote()
            ->signingState(ESigningState::PENDING)
            ->create();

        $newState = ESigningState::INACTIVATED;
        $request = $this->createStateUpdateRequest(['state' => $newState->value]);

        $this->getController()->stateUpdate($signing->getSigningId(), $request);

        $signing->refresh();
        $this->assertSame($newState, $signing->getSigningState()->getState());
    }

    /**
     * @param mixed $payload
     * @return Request
     */
    protected function createStateUpdateRequest(mixed $payload): Request
    {
        return Request::create('', 'PUT', [], [], [], [], json_encode($payload));
    }

    /**
     * @return SigningsController
     */
    protected function getController(): SigningsController
    {
        return static::getContainer()->get(SigningsController::class);
    }
}
