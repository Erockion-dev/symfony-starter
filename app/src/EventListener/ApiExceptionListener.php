<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ApiExceptionListener
{
    public function __construct(
        #[Autowire('%kernel.debug%')] private readonly bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $message = $exception->getMessage();
            $headers = $exception->getHeaders();

            $previous = $exception->getPrevious();
            if ($previous instanceof ValidationFailedException) {
                $message = [];
                foreach ($previous->getViolations() as $violation) {
                    $message[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
                }
            }
        } else {
            if ($this->debug) {
                return;
            }
            $status = 500;
            $message = 'Internal server error';
            $headers = [];
        }

        $event->setResponse(new JsonResponse([
            'message' => $message,
            'error' => Response::$statusTexts[$status] ?? 'Error',
            'statusCode' => $status,
        ], $status, $headers));
    }
}
