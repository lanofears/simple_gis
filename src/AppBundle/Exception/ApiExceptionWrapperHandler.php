<?php

namespace AppBundle\Exception;

use FOS\RestBundle\View\ExceptionWrapperHandlerInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

class ApiExceptionWrapperHandler implements ExceptionWrapperHandlerInterface
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
     * {@inheritDoc}
     */
    public function wrap($data)
    {
        /** @var FlattenException $exception */
        $exception = $data['exception'];

        switch ($exception->getClass()) {
            case BadRequestHttpException::class:
                $code = Response::HTTP_BAD_REQUEST;
                $message = self::$custom_messages[$code];
                break;
            case MethodNotAllowedHttpException::class:
                $code = Response::HTTP_METHOD_NOT_ALLOWED;
                $message = self::$custom_messages[$code];
                break;
            case UnsupportedMediaTypeHttpException::class:
                $code = Response::HTTP_UNSUPPORTED_MEDIA_TYPE;
                $message = self::$custom_messages[$code];
                break;
            case NotFoundHttpException::class:
                $code = Response::HTTP_NOT_FOUND;
                $message = self::$custom_messages[$code];
                break;
            case WrongParametersException::class:
                $code = Response::HTTP_BAD_REQUEST;
                $message = $exception->getMessage();
                break;
            case EmptyResultException::class:
                $code = Response::HTTP_NOT_FOUND;
                $message = $exception->getMessage();
                break;
            default:
                $code = Response::HTTP_INTERNAL_SERVER_ERROR;
                $message = self::$custom_messages[$code];
        }

        $new_exception = [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];

        return $new_exception;
    }
}