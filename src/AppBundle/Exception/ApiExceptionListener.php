<?php

namespace AppBundle\Exception;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionListener implements EventSubscriberInterface
{
    protected static $custom_messages = [
        Response::HTTP_BAD_REQUEST => 'Неверно сформированный запрос. В параметрах запроса присутствует ошибка -
                указан несуществующий параметр, или значение параметра имеет неверный формат',
        Response::HTTP_NOT_FOUND => 'Запрашиваемый ресур не найден',
        Response::HTTP_METHOD_NOT_ALLOWED => 'Указанный метод не поддерживается',
        Response::HTTP_UNSUPPORTED_MEDIA_TYPE => 'Указанный формат не поддерживается ресурсом',
        Response::HTTP_INTERNAL_SERVER_ERROR => 'Во время обработки запроса произошла внутренняя ошибка сервера'
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Конструктор обработчика ошибок
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $this->logException($exception);
        $headers = [];

        if ($exception instanceof HttpExceptionInterface) {
            $code = $exception->getStatusCode();
            $message = isset(self::$custom_messages[$code]) ? self::$custom_messages[$code] :
                self::$custom_messages[Response::HTTP_INTERNAL_SERVER_ERROR];

            $headers = $exception->getHeaders();
        }
        else if ($exception instanceof WrongParametersException) {
            $code = Response::HTTP_BAD_REQUEST;
            $message = $exception->getMessage();
        }
        else if ($exception instanceof EmptyResultException) {
            $code = Response::HTTP_NOT_FOUND;
            $message = $exception->getMessage();
        }
        else {
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = self::$custom_messages[$code];
        }

        $response = new JsonResponse([
            'success' => false,
            'code' => $code,
            'message' => $message
        ]);
        $response->setStatusCode($code);
        if ($headers) {
            $response->headers->replace($headers);
        }

        $event->setResponse($response);
    }

    /**
     * Запись в лог данных об ошибке
     *
     * @param Exception $exception
     */
    protected function logException(Exception $exception)
    {
        $message = sprintf('Uncaught PHP Exception %s: "%s" at %s line %s',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        if ($this->logger) {
            if (!$exception instanceof HttpExceptionInterface || $exception->getStatusCode() >= 500) {
                $this->logger->critical($message, [ 'exception' => $exception ]);
            }
            else {
                $this->logger->error($message, [ 'exception' => $exception ]);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'onKernelException',
        );
    }
}