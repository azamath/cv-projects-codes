<?php

namespace App\EventSubscriber;

use App\Controller\ValidatesSignature;
use App\Services\SignatureValidatorService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SignatureValidatorSubscriber implements EventSubscriberInterface
{
    private SignatureValidatorService $signatureValidator;

    public function __construct(SignatureValidatorService $signatureValidator)
    {
        $this->signatureValidator = $signatureValidator;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        // when a controller class defines multiple action methods, the controller
        // is returned as [$controllerInstance, 'methodName']
        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof ValidatesSignature) {
            $this->signatureValidator->validate($event->getRequest());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
