<?php

namespace Tests\AppBundle\Controller;

use Tests\AppBundle\Common\ApiControllerTest;

/**
 * Тестирование контроллера для ресурса организаций
 *
 * @author Aleksey Skryazhevskiy
 */
class OrganizationControllerTest extends ApiControllerTest
{
    /**
     * Тестирование корректных запросов
     */
    public function testSuccess()
    {
        $client = static::createClient();
        $client-> request('GET', '/api/organization/?address=лесосечная, 2');

        $this->assertJsonPagedOk($client->getResponse(),
            'Ответ на запрос /api/organization/?address=xxx не является валидным ответом API');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidOrganization($data);

        $client = static::createClient();
        $client-> request('GET', '/api/organization/?location=54.890313,83.089735,100');

        $this->assertJsonPagedOk($client->getResponse(),
            'Ответ на запрос /api/organization/?location=xxx не является валидным ответом API');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidOrganization($data);

        $client = static::createClient();
        $client-> request('GET', '/api/organization/?location=54.890313,83.089735,100&order=name');

        $this->assertJsonPagedOk($client->getResponse(),
            'Ответ на запрос /api/organization/?location=xxx&order=name не является валидным ответом API');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidOrganization($data);

        $client = static::createClient();
        $client-> request('GET', '/api/organization/?location=54.890313,83.089735,100&order=address');

        $this->assertJsonPagedOk($client->getResponse(),
            'Ответ на запрос /api/organization/?location=xxx&order=address не является валидным ответом API');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidOrganization($data);

        $organization_id = isset($data['organizations'][0]['id']) ? $data['organizations'][0]['id'] : 0;
        $client = static::createClient();
        $client-> request('GET', '/api/organization/'.$organization_id);

        $this->assertJsonOk($client->getResponse(),
            'Ответ на запрос /api/organization/id не является валидным ответом API');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidOrganization($data);
    }

    /**
     * Тестирование некорректных запросов
     */
    public function testError()
    {
        $client = static::createClient();
        $client-> request('GET', '/api/organization/?test=новосибирск');
        $this->assertValidError($client->getResponse(), 400,
            'Ответ на запрос /api/organization/?text=xxx не является корректной ошибкой API');

        $client = static::createClient();
        $client-> request('GET', '/api/organization/?location=новосибирск');
        $this->assertValidError($client->getResponse(), 400,
            'Ответ на запрос /api/organization/?location=xxx c некорректными координатами не является корректной ошибкой API');

        $client = static::createClient();
        $client-> request('GET', '/api/organization/?address=новосибирск&order=test');
        $this->assertValidError($client->getResponse(), 400,
            'Ответ на запрос /api/organization/?address=xxx&order=test c некорректными координатами не является корректной ошибкой API');

        $client = static::createClient();
        $client-> request('GET', '/api/organization/?address=TEST_X_TEST');
        $this->assertValidError($client->getResponse(), 404,
            'Ответ на запрос /api/organization/?address=TEST_X_TEST не является корректной ошибкой API');

        $client = static::createClient();
        $client-> request('GET', '/api/organization/0');
        $this->assertValidError($client->getResponse(), 404,
            'Ответ на запрос /api/organization/0 не является корректной ошибкой API');
    }

    /**
     * Проверка данных объекта на корректность формата
     *
     * @param array $data
     */
    private function assertValidOrganization($data)
    {
        $this->assertValidObject($data, 'organizations', ['id', 'name', 'building', 'phones', 'rubrics'],
            'Объекты организаций в ответе не содержат минимально необходимых полей');
    }
}