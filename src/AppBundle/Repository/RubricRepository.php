<?php

namespace AppBundle\Repository;

use AppBundle\Entity\RecursiveRubricIterator;
use AppBundle\Entity\Rubric;
use AppBundle\Exception\WrongParametersException;
use AppBundle\Extensions\References\SearchFilters;
use AppBundle\Extensions\Utils\FilterTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use RecursiveIteratorIterator;

/**
 * Репозиторий для работы с рубриками
 *
 * @author Aleksey Skryazhevskiy
 */
class RubricRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return $this->createQueryBuilder('r')
            ->select('r, c')
            ->addSelect('COALESCE(r.parent_id,0) as HIDDEN parent_order')
            ->leftJoin('r.children', 'c');
    }

    /**
     * {@inheritDoc}
     */
    protected function applyParameters($query_builder, $params)
    {
        if (array_key_exists(SearchFilters::Q_NAME, $params)) {
            $address = FilterTransformer::createSubStringFilter($params[SearchFilters::Q_NAME]);
            $query_builder
                ->andWhere('LOWER(r.name) LIKE LOWER(:name)')
                ->setParameter('name', $address);
        }

        if (array_key_exists(SearchFilters::Q_PARENT, $params)) {
            $query_builder
                ->andWhere('r.parent = :parent')
                ->setParameter('parent', $params[SearchFilters::Q_PARENT]);
        }

        if (array_key_exists(SearchFilters::Q_ORDER, $params)) {
            $order_values = ['name' => 'r.name', 'parent' => 'parent_order'];
            foreach (explode(',', $params[SearchFilters::Q_ORDER]) as $order) {
                if (!array_key_exists($order, $order_values)) {
                    throw new WrongParametersException(
                        'Неверное значение поля для портировки, доллжно быть "name", или "parent", получено "'.$order.'"');
                }

                $query_builder->addOrderBy($order_values[$order]);
            }
        }
        else {
            $query_builder
                ->addOrderBy('parent_order')
                ->addOrderBy('r.name');
        }

        return $query_builder;
    }

    public function findById($id) {
        return $this->getResult($this->getQueryBuilder()
            ->where('r.id = :id')
            ->setParameter('id', $id)
        );
    }

    /**
     * Рекурсивынй поиск рубрики, ищет саму рубррику и все дочерние рубрики
     *
     * @param Rubric|int $rubric
     * @return Rubric[]
     */
    public function findByIdRecursive($rubric)
    {
        if (!($rubric instanceof Rubric)) {
            $rubric = $this->find($rubric);
        }
        if (!$rubric) {
            return [];
        }

        $rubrics = [];
        $rubric_iterator = new RecursiveRubricIterator(new ArrayCollection([$rubric]));
        $recursive_rubric_iterator = new RecursiveIteratorIterator($rubric_iterator, RecursiveIteratorIterator::SELF_FIRST);
        /** @var Rubric $rubric */
        foreach ($recursive_rubric_iterator as $rubric_item) {
            $rubrics[$rubric_item->getId()] = $rubric_item;
        }

        return $rubrics;
    }

    /**
     * Поиск по подстроке входящей в наименование рубрики
     *
     * @param string $name
     * @return Rubric[]
     */
    public function findByNamePart($name)
    {
        $name = FilterTransformer::createSubStringFilter($name);
        return $this->getResult($this->getQueryBuilder()
            ->where('r.name LIKE :name')
            ->setParameter('name', $name)
        );
    }
}