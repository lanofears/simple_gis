<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Rubric;
use Tests\AppBundle\Common\EntityTest;

class RubricTest extends EntityTest
{
    public function testFindByNamePart()
    {
        $rubric_name = 'Легковые';
        $rubrics = $this->em
            ->getRepository('AppBundle:Rubric')
            ->findByNamePart($rubric_name);

        $this->assertCount(1, $rubrics, 'Найдена более/менее одной рубрики с наименованием содержащим "'.$rubric_name.'"');
    }
}