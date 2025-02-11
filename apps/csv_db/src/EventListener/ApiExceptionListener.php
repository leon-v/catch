<?php

/**
 * API Exception listener file
 */

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * API Exception listener class
 * Simply returns exceptions as JSON for API clients to consume natively.
 */
class ApiExceptionListener implements EventSubscriberInterface
{

    /**
     * Listen for exceptions and return them as JSON
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Only handle API exceptions
        if (!is_a($exception, \App\Exception\Api::class)) {
            return;
        }

        // Create a JSON response
        $response = new JsonResponse([
            'error' => $exception->getMessage(),
        ]);

        // Set the status code from the exception
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
        } else {
            $response->setStatusCode(JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $event->setResponse($response);
    }

    /**
     * Subscribe to the Kernel Exception event
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
