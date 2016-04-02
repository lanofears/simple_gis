<?php

namespace AppBundle\Repository\Paginator;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\LimitSubqueryWalker;

/**
 * Класс для постраничной отрисовки на базе аналогичного класса из пакета DoctrineExtensions.
 *
 * Изменения:
 *  - вывод результата в массив, для избежания необходимости преобразования ArrayIterator в array
 *  - использования кастомного WhereInWalker, который полностью заменяет все условия на WHERE id IN (ids),
 *    в связи с чем добавлена фильтрация параметров зароса по id
 */
class Paginator
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var int
     */
    private $count;

    /**
     * Конструктор класса
     *
     * @param Query|QueryBuilder $query
     */
    public function __construct($query)
    {
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }

        $this->query = $query;
    }

    /**
     * Исходный запрос
     *
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Подсчет кол-ва строк
     *
     * @return int
     */
    public function count()
    {
        if (!is_null($this->count)) {
            return $this->count;
        }

        try {
            $this->count = array_sum(array_map('current', $this->getCountQuery()->getScalarResult()));
        }
        catch(NoResultException $e) {
            $this->count = 0;
        }

        return $this->count;
    }

    /**
     * Выполнение запроса с постраничным выводом результатов
     *
     * @return array
     */
    public function getResult()
    {
        $offset = $this->query->getFirstResult();
        $length = $this->query->getMaxResults();

        $subQuery = $this->cloneQuery($this->query);

        $this->appendTreeWalker($subQuery, LimitSubqueryWalker::class);

        $subQuery
            ->setFirstResult($offset)
            ->setMaxResults($length);

        $ids = array_map('current', $subQuery->getScalarResult());

        if (count($ids) == 0) {
            return [];
        }

        $whereInQuery = $this->cloneQuery($this->query);
        $this->appendTreeWalker($whereInQuery, WhereInWalker::class);
        $whereInQuery
            ->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, count($ids))
            ->setParameter(WhereInWalker::PAGINATOR_ID_ALIAS, $ids)
            ->setFirstResult(null)->setMaxResults(null)
            ->setCacheable($this->query->isCacheable());

        /* @var $parameters Parameter[] */
        $parameters = $whereInQuery->getParameters();
        $parser = new Parser($whereInQuery);
        $parameterMappings = $parser->parse()->getParameterMappings();

        foreach ($parameters as $key => $parameter) {
            $parameterName = $parameter->getName();

            if( ! (isset($parameterMappings[$parameterName]) || array_key_exists($parameterName, $parameterMappings))) {
                unset($parameters[$key]);
            }
        }

        $result = $whereInQuery
            ->setParameters($parameters)
            ->getResult();

        return $result;
    }

    /**
     * Клонирование запроса с переносом параметров и хинтов
     *
     * @param Query $query The query.
     * @return Query The cloned query.
     */
    private function cloneQuery(Query $query)
    {
        /* @var $cloneQuery Query */
        $cloneQuery = clone $query;

        $cloneQuery->setParameters(clone $query->getParameters());

        foreach ($query->getHints() as $name => $value) {
            $cloneQuery->setHint($name, $value);
        }

        return $cloneQuery;
    }

    /**
     * Добавление TreeWalker
     *
     * @param Query $query
     * @param string $walkerClass
     */
    private function appendTreeWalker(Query $query, $walkerClass)
    {
        $hints = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);

        if ($hints === false) {
            $hints = array();
        }

        $hints[] = $walkerClass;
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $hints);
    }

    /**
     * Создание запроса для подсчета кол-ва строк
     *
     * @return Query
     */
    private function getCountQuery()
    {
        /* @var $countQuery Query */
        $countQuery = $this->cloneQuery($this->query);

        if ( ! $countQuery->hasHint(CountWalker::HINT_DISTINCT)) {
            $countQuery->setHint(CountWalker::HINT_DISTINCT, true);
        }

        $this->appendTreeWalker($countQuery, CountWalker::class);

        $countQuery
            ->setFirstResult(null)
            ->setMaxResults(null);

        return $countQuery;
    }
}
