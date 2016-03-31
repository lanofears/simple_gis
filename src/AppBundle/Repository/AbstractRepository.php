<?php

namespace AppBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;

abstract class AbstractRepository extends EntityRepository
{
    /**
     * Создание основного запроса
     *
     * @return QueryBuilder
     */
    protected abstract function getQueryBuilder();

    /**
     * Применение параметров к запросу
     *
     * @param QueryBuilder $query_builder
     * @param array $params
     * @return QueryBuilder
     */
    protected abstract function applyParameters($query_builder, $params);

    /**
     * Выдача результатов запроса
     *
     * @param QueryBuilder $query_builder
     * @param bool $single
     * @return mixed
     */
    protected function getResult($query_builder, $single = false) {
        $query = $query_builder->getQuery();
        return $single ? $query->getSingleResult() : $query->getResult();
    }

    /**
     * Свободный поиск по параметрам API
     *
     * @param $params
     * @return mixed
     */
    public function findByParams($params = []) {
        return $this->getResult($this->applyParameters($this->getQueryBuilder(), $params));
    }
}