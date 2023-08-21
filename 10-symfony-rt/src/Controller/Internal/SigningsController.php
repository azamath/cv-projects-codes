<?php

namespace App\Controller\Internal;

use App\Controller\ValidatesSignature;
use App\Entity\Signing;
use App\Enum\ESigningState;
use App\Enum\EStateUpdateMethod;
use App\Traits\HasLogger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SigningsController extends AbstractController implements ValidatesSignature, LoggerAwareInterface
{
    use HasLogger;

    public function __construct(private EntityManagerInterface $manager)
    {
    }

    #[Route('internal/signings/{signingId<\d+>}/state', name: 'signing_state_update', methods: ['PUT'])]
    public function stateUpdate(int $signingId, Request $request): Response
    {
        $payload = json_decode($request->getContent());
        if (!$payload || !isset($payload->state) || !in_array($payload->state, ESigningState::values(), true)) {
            throw new UnprocessableEntityHttpException('Request payload is not valid.');
        }

        $signing = $this->manager->getRepository(Signing::class)->find($signingId);
        if (!$signing) {
            throw $this->createNotFoundException(
                'No signing found for id ' . $signingId
            );
        }

        $state = ESigningState::from($payload->state);
        $method = EStateUpdateMethod::API;
        $signing->getSigningState()->setState($state);
        $signing->getSigningState()->setStateUpdateMethod($method);
        $this->manager->flush();

        $this->logNotice("{$signing}: state was updated via internal API, state: {$state->name}");

        return $this->json([
            'message' => 'Signing state has been successfully updated.',
        ]);
    }
}
