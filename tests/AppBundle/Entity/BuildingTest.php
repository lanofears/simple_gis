<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Building;
use Tests\AppBundle\Common\EntityTest;

/**
 * Тестирование работы со зданиями на уровне ORM
 *
 * @author Aleksey Skryazhevskiy
 */
class BuildingTest extends EntityTest
{
    /**
     * Тестирование поиска по адресу
     */
    public function testFindByAddress()
    {
        $address = 'Лесосечная, 7';
        $buildings = $this->em
            ->getRepository('AppBundle:Building')
            ->findByAddress($address);

        $this->assertCount(1, $buildings, 'Найдено более/менее одного здания адрес которого содержит "'.$address.'""');
    }

    /**
     * Тестирование поиска по месту нахождения
     */
    public function testFindByRadius()
    {
        $radius = 100;
        $latitude = 54.890313;
        $longitude = 83.089735;

        $buildings = $this->em
            ->getRepository('AppBundle:Building')
            ->findByDistance($latitude, $longitude, $radius);

        $this->assertCount(1, $buildings,
            'Найдено более/менее чем 1 здание в радиусе '.$radius.' м от ('.$latitude.', '.$longitude.')');
    }
}
