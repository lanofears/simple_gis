<?php

namespace Tests\AppBundle\Controller;

use Tests\AppBundle\Common\ApiControllerTest;

/**
 * Тестирование контроллера для ресурса зданий
 *
 * @author Aleksey Skryazhevskiy
 */
class BuildingControllerTest extends ApiControllerTest
{
    /**
     * Тестирование корректных запросов
     */
    public function testSuccess()
    {
        $client = static::createClient();
        $client-> request('GET', '/api/building/?address=лесосечная, 2');

        $this->assertJsonPagedOk($client->getResponse(),
            'Ответ на запрос /api/building/?address=xxx не является валидным ответом API');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidBuildings($data);

        $client = static::createClient();
        $client-> request('GET', '/api/building/?location=54.890313,83.089735,100');

        $this->assertJsonPagedOk($client->getResponse(),
            'Ответ на запрос /api/building/?location=xxx не является валидным ответом API');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidBuildings($data);

        $building_id = isset($data['buildings'][0]['id']) ? $data['buildings'][0]['id'] : 0;
        $client = static::createClient();
        $client-> request('GET', '/api/building/'.$building_id);

        $this->assertJsonOk($client->getResponse(),
            'Ответ на запрос /api/building/id не является валидным ответом API');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidObject($data, 'buildings', ['id', 'address', 'latitude', 'longitude', 'organizations'],
            'Объект здания в ответе не содержет минимально необходимых полей');
    }

    /**
     * Тестирование некорректных запросов
     */
    public function testError()
    {
        $client = static::createClient();
        $client-> request('GET', '/api/building/?test=новосибирск');
        $this->assertValidError($client->getResponse(), 400,
            'Ответ на запрос /api/building/?text=xxx не является корректной ошибкой API');

        $client = static::createClient();
        $client-> request('GET', '/api/building/?location=новосибирск');
        $this->assertValidError($client->getResponse(), 400,
            'Ответ на запрос /api/building/?location=xxx c некорректными координатами не является корректной ошибкой API');

        $client = static::createClient();
        $client-> request('GET', '/api/building/?address=TEST_X_TEST');
        $this->assertValidError($client->getResponse(), 404,
            'Ответ на запрос /api/building/?address=TEST_X_TEST не является корректной ошибкой API');

        $client = static::createClient();
        $client-> request('GET', '/api/building/0');
        $this->assertValidError($client->getResponse(), 404,
            'Ответ на запрос /api/building/0 не является корректной ошибкой API');
    }

    /**
     * Проверка данных объекта на корректность формата
     *
     * @param array $data
     */
    private function assertValidBuildings($data)
    {
        $this->assertValidObject($data, 'buildings', ['id', 'address', 'latitude', 'longitude'],
            'Объекты зданий в ответе не содержат минимально необходимых полей');
    }
}