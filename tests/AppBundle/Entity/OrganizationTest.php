<?php

namespace Tests\AppBundle\Entity;

use Tests\AppBundle\Common\EntityTest;

class OrganizationTest extends EntityTest
{
    public function testFindByRubricName() {
        $rubric = 'Полуфабрикаты оптом';
        $organizations = $this->em->getRepository('AppBundle:Organization')
            ->findByRubricName($rubric);

        $this->assertCount(2, $organizations,
            'Найдено более/меньше 2-х организаций в рубрике "'.$rubric.'" (по наименованию)');
    }

    public function testFindByRubricId() {
        $rubric = $this->em->getRepository('AppBundle:Rubric')
            ->findOneBy([ 'name' => 'Автомобили' ]);

        $organizations = $this->em->getRepository('AppBundle:Organization')
            ->findByRubric($rubric);

        $this->assertCount(4, $organizations,
            'Найдено более/меньше 2-х организаций в рубрике "'.$rubric->getName().'" (по идентификатору)');
    }

    public function testFindByAddressPart() {
        $address = 'Лесосечная, 7';
        $organizations = $this->em->getRepository('AppBundle:Organization')
            ->findByAddressPart($address);

        $this->assertCount(3, $organizations,
            'Найдено более/меньше 3-х организаций в c адресом содержащим "'.$address.'"');
    }

    public function testFindByBuildingId() {
        $building = $this->em->getRepository('AppBundle:Building')
            ->findOneBy([ 'address' => 'г. Новосибирск, ул. Лесосечная, д. 7' ]);
        $organizations = $this->em->getRepository('AppBundle:Organization')
            ->findByBuilding($building);

        $this->assertCount(3, $organizations,
            'Найдено более/меньше 3-х организаций по адресу "'.$building->getAddress().'"');
    }

    public function testFindByDistance() {
        $radius = 100;
        $latitude = 54.890313;
        $longitude = 83.089735;

        $organizations = $this->em->getRepository('AppBundle:Organization')
            ->findByDistance($latitude, $longitude, $radius);

        $this->assertCount(3, $organizations,
            'Найдено более/менее 3-х организаций в радиусе '.$radius.' м от ('.$latitude.', '.$longitude.')');
    }
}