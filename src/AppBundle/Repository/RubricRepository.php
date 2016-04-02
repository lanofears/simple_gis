<?php

namespace AppBundle\Repository;

use AppBundle\Entity\RecursiveRubricIterator;
use AppBundle\Entity\Rubric;
use AppBundle\Exception\WrongParametersException;
use AppBundle\Extensions\References\SearchFilters;
use AppBundle\Extensions\Utils\ArrayValidator;
use AppBundle\Extensions\Utils\FilterTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use RecursiveIteratorIterator;

/**
 * Репозиторий для работы с рубриками
 *
 * @author Aleksey Skryazhevskiy
 */
class RubricRepository extends AbstractRepository
{
    const ORDER_BY_NAME = 'name';
    const ORDER_BY_PARENT = 'parent';

    protected static $order_mapping = [
        self::ORDER_BY_NAME     => 'r.name',
        self::ORDER_BY_PARENT   => 'parent_order'
    ];

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
     * Добавление в запрос фильтра по наименованию рубрики
     *
     * @param QueryBuilder $query_builder
     * @param string $name
     * @return QueryBuilder
     */
    private function applyFilterByName($query_builder, $name)
    {
        $name = FilterTransformer::createSubStringFilter($name);

        return $query_builder
            ->andWhere('LOWER(r.name) LIKE LOWER(:name)')
            ->setParameter('name', $name);
    }

    /**
     * @param QueryBuilder $query_builder
     * @param int $parent
     * @return QueryBuilder
     */
    private function applyFilterByParent($query_builder, $parent)
    {
        return $query_builder
            ->andWhere('r.parent = :parent')
            ->setParameter('parent', $parent);
    }

    /**
     * {@inheritDoc}
     */
    protected function applyParameters($query_builder, $params)
    {
        if (array_key_exists(SearchFilters::Q_NAME, $params)) {
            $query_builder = $this->applyFilterByName($query_builder, $params[SearchFilters::Q_NAME]);
        }

        if (array_key_exists(SearchFilters::Q_PARENT, $params)) {
            $query_builder = $this->applyFilterByParent($query_builder, (int)$params[SearchFilters::Q_PARENT]);
        }

        $sort = [ self::ORDER_BY_PARENT, self::ORDER_BY_NAME ];
        if (array_key_exists(SearchFilters::Q_ORDER, $params)) {
            $sort = explode(',', $params[SearchFilters::Q_ORDER]);
            if (!ArrayValidator::isSubsetOf($sort, self::$order_mapping)) {
                throw new WrongParametersException(
                    'Неверное значение поля для портировки, может принимать значения "'.implode(self::$order_mapping).'", '.
                    'получено "'.$params[SearchFilters::Q_ORDER].'"');
            }
        }
        $query_builder = $this->applySort($query_builder, $sort);

        return $query_builder;
    }

    public function findById($id)
    {
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
        return $this->getResult(
            $this->applyFilterByName($this->getQueryBuilder(), $name)
        );
    }
}