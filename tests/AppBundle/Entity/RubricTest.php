<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Rubric;
use Tests\AppBundle\Common\EntityTest;

/**
 * Тестирование работы с рубриками на уровне ORM
 *
 * @author Aleksey Skryazhevskiy
 */
class RubricTest extends EntityTest
{
    /**
     * Тестирование поиска по наименованию рубрики
     */
    public function testFindByNamePart()
    {
        $rubric_name = 'Легковые';
        $rubrics = $this->em
            ->getRepository('AppBundle:Rubric')
            ->findByNamePart($rubric_name);

        $this->assertCount(1, $rubrics, 'Найдена более/менее одной рубрики с наименованием содержащим "'.$rubric_name.'"');
    }
}