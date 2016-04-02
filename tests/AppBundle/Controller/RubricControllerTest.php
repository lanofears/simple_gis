<?php

namespace Tests\AppBundle\Controller;

use Tests\AppBundle\Common\ApiControllerTest;

/**
 * Тестирование контроллера для ресурса рубрик
 *
 * @author Aleksey Skryazhevskiy
 */
class RubricControllerTest extends ApiControllerTest
{
    /**
     * Тестирование некорректных запросов
     */
    public function testSuccess()
    {
        $client = static::createClient();
        $client-> request('GET', '/api/rubric/?name=Автомобили');

        $this->assertJsonPagedOk($client->getResponse(),
            'Ответ на запрос /api/rubric/?name=xxx не является валидным ответом API');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidObject($data, 'rubrics', ['id', 'parent_id', 'name'],
            'Объекты рубрик в ответе не содержат минимально необходимых полей');

        $rubric_id = isset($data['rubrics'][0]['id']) ? $data['rubrics'][0]['id'] : 0;
        $client = static::createClient();
        $client-> request('GET', '/api/rubric/'.$rubric_id);

        $this->assertJsonOk($client->getResponse(),
            'Ответ на запрос /api/rubric/id не является валидным ответом API');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidObject($data, 'rubrics', ['id', 'parent_id', 'name', 'children'],
            'Объект рубрики в ответе не содержет минимально необходимых полей');
    }

    /**
     * Тестирование некорректных запросов
     */
    public function testError() {
        $client = static::createClient();
        $client-> request('GET', '/api/rubric/?test=Автомобили');

        $this->assertValidError($client->getResponse(), 400,
            'Ответ на запрос /api/rubric/?text=xxx не является корректной ошибкой API');

        $client = static::createClient();
        $client-> request('GET', '/api/rubric/?name=TEST_X_TEST');

        $this->assertValidError($client->getResponse(), 404,
            'Ответ на запрос /api/rubric/?name=TEST_X_TEST не является корректной ошибкой API');
    }
}
