<?php

namespace AppBundle\Repository;

use AppBundle\Exception\WrongParametersException;
use AppBundle\Extensions\References\SearchFilters;
use AppBundle\Extensions\Utils\NumericValidator;
use AppBundle\Repository\Paginator\Paginator;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;

/**
 * Базовый (абстрактный) класс репозитория
 *
 * @author Aleksey Skryazhevskiy
 */
abstract class AbstractRepository extends EntityRepository
{
    const DEFAULT_PAGE = 1;
    const DEFAULT_LIMIT = 100;

    protected static $order_mapping = [];

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
     * Добавление сортировки
     *
     * @param QueryBuilder $query_builder
     * @param array $sort
     * @return QueryBuilder
     */
    protected function applySort($query_builder, $sort = [])
    {
        $sort = array_intersect($sort, array_keys(static::$order_mapping));
        $sort = $sort ? $sort : [];

        foreach ($sort as $field) {
            $query_builder->addOrderBy(static::$order_mapping[$field]);
        }

        return $query_builder;
    }

    /**
     * Приминение параметров постраничного вывода
     *
     * @param QueryBuilder $query_builder
     * @param $params
     * @return QueryBuilder
     * @throws WrongParametersException
     */
    private function applyPaginatorParameters($query_builder, $params)
    {
        $limit = array_key_exists(SearchFilters::Q_LIMIT, $params) ? $params[SearchFilters::Q_LIMIT] : self::DEFAULT_LIMIT;
        if (!NumericValidator::isIntConstraint($limit, 1, 1000)) {
            throw new WrongParametersException('Значение параметра "limit" должно быть целым числом в диапазоне от 1 до 1000, '.
                'получено "'.$limit.'"');
        }
        $query_builder->setMaxResults($limit);

        $page = array_key_exists(SearchFilters::Q_PAGE, $params) ? $params[SearchFilters::Q_PAGE] : self::DEFAULT_PAGE;
        if (!NumericValidator::isIntConstraint($page, 1)) {
            throw new WrongParametersException('Значение параметра "page" должно быть целым числом большим, либо равным 1, '.
                'получено "'.$page.'"');
        }
        --$page;
        $query_builder->setFirstResult($page * $limit);

        return $query_builder;
    }

    /**
     * Выдача результатов запроса
     *
     * @param QueryBuilder $query_builder
     * @param bool $single
     * @return mixed
     */
    protected function getResult($query_builder, $single = false)
    {
        $query = $query_builder->getQuery();
        return $single ? $query->getSingleResult() : $query->getResult();
    }

    /**
     * Свободный поиск по параметрам API
     *
     * @param $params
     * @return mixed
     */
    public function findByParams($params = [])
    {
        $query = $this->applyParameters($this->getQueryBuilder(), $params);

        return $this->getResult($query);
    }

    /**
     * Получение компонента для постраничного вывода данных
     *
     * @param array $params
     * @return Paginator
     */
    public function findByParamsPaged($params = [])
    {
        $query = $this->applyPaginatorParameters($this->getQueryBuilder(), $params);

        return new Paginator($this->applyParameters($query, $params), true);
    }
}