<?php

namespace App\Tests\Unit\Handler;

use App\Enum\ESigningState;
use App\Handler\ResolveQuoteStatesHandler;
use App\Repository\QuoteRepository;
use App\Tests\Traits\MocksDoctrine;
use PHPUnit\Framework\TestCase;

class ResolveQuoteStatesHandlerTest extends TestCase
{
    use MocksDoctrine;

    /**
     * @dataProvider resolveStateProvider
     */
    public function testResolveState(array $states, ESigningState $expected)
    {
        $handler = $this->getHandler();
        $this->assertEquals($expected, $handler->resolveState($states));
    }

    public function resolveStateProvider(): array
    {
        return [
            [
                [],
                ESigningState::PENDING,
            ],
            [
                [ESigningState::PENDING, ESigningState::PENDING],
                ESigningState::PENDING,
            ],
            [
                [ESigningState::INACTIVATED, ESigningState::PENDING],
                ESigningState::INACTIVATED,
            ],
            [
                [ESigningState::PENDING, ESigningState::INACTIVATED],
                ESigningState::INACTIVATED,
            ],
            [
                [ESigningState::LOST, ESigningState::INACTIVATED, ESigningState::PENDING],
                ESigningState::LOST,
            ],
            [
                [ESigningState::SOLD, ESigningState::INACTIVATED, ESigningState::PENDING, ESigningState::LOST],
                ESigningState::SOLD,
            ],
            [
                [ESigningState::INACTIVATED, ESigningState::PENDING, ESigningState::SOLD, ESigningState::LOST],
                ESigningState::SOLD,
            ],
        ];
    }

    protected function getHandler(): ResolveQuoteStatesHandler
    {
        return new ResolveQuoteStatesHandler(
            $this->createMock(QuoteRepository::class),
            $this->getMockDoctrine(),
        );
    }
}
