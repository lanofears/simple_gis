<?php

namespace Tests\AppBundle\Common;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Базовый класс теста для контроллера API
 *
 * @author Aleksey Skryazhevskiy
 */
class ApiControllerTest extends WebTestCase
{
    /**
     * Проверка на корректность успешно выполненного запроса к API
     *
     * @param Response $response
     * @param string $message
     * @return mixed
     */
    protected function assertJsonOk(Response $response, $message = '')
    {
        $this->assertThat($response, new IsOkJsonResponseConstraint(), $message);
    }

    /**
     * Проверка объекта (в виде массива) на то, что он содержит минимально необходимые поля
     *
     * @param array $object
     * @param string $data_key
     * @param array $fields
     * @param string $message
     */
    protected function assertValidObject($object, $data_key, $fields, $message = '')
    {
        $this->assertThat($object, new IsValidResponseDataConstraint($data_key, $fields), $message);
    }

    /**
     * Проверка на возврат корректной ошибки при неверном запросе к API
     *
     * @param Response $response
     * @param int $code
     * @param string $message
     */
    protected function assertValidError(Response $response, $code, $message = '')
    {
        $this->assertThat($response, new IsErrorJsonConstraint($code), $message);
    }
}