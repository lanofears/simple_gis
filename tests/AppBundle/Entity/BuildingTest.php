<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Building;
use Tests\AppBundle\Common\EntityTest;

class BuildingTest extends EntityTest
{
    public function testFindByAddress()
    {
        $address = 'Лесосечная, 7';
        $buildings = $this->em
            ->getRepository('AppBundle:Building')
            ->findByAddress($address);

        $this->assertCount(1, $buildings, 'Найдено более/менее одного здания адрес которого содержит "'.$address.'""');
    }

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
