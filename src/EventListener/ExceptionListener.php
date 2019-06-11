<?php
// src/EventListener/ExceptionListener.php
namespace App\EventListener;

use App\Base\Exception\DataException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    /** Constants */
    const DEV_ENVIRONMENT = 'dev';
    const MAIN_PHASE = 'main';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Sets funcionality for kernel exception
     *
     * @return void
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();

        $response = new JsonResponse();

        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ($exception instanceof DataException) {
            $response = $this->getDataExceptionResponse($exception);
        } else if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
            $response->setData(["message" => $exception->getMessage()]);
        } else {
            $response->setData(["message" => $exception->getMessage()]);
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // sends the modified response object to the event
        $event->setResponse($response);
    }

    /**
     * Gets data exception response
     *
     * @param DataException $exception
     * @return JsonResponse
     */
    protected function getDataExceptionResponse($exception)
    {
        var_dump($this->container->get('kernel')->getEnvironment());die();
        $response = new JsonResponse();

        $data = $exception->getData();

        $response->setStatusCode($exception->getStatusCode());
        $response->headers->replace($exception->getHeaders());
        $response->setData($exception->getData());

        return $response;
    }
}
