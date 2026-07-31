<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsEventListener(event: 'kernel.exception')]
class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof NotFoundHttpException) {

            $event->setResponse(
                new JsonResponse([
                    'error' => 'Endpoint not found'
                ], 404)
            );

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {

            $event->setResponse(
                new JsonResponse([
                    'error' => $exception->getMessage() ?: 'HTTP error'
                ], $exception->getStatusCode())
            );

            return;
        }

        $event->setResponse(
            new JsonResponse([
                'error' => 'Internal server error'
            ], 500)
        );
    }
}