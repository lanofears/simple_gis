<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Building;
use AppBundle\Exception\WrongParametersException;
use AppBundle\Extensions\References\SearchFilters;
use AppBundle\Extensions\Utils\FilterTransformer;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Репозиторий для работы с зданиями
 *
 * @author Aleksey Skryazhevskiy
 */
class BuildingRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return $this->createQueryBuilder('b')
            ->select('b')
            ->addOrderBy('b.address');
    }

    /**
     * Добавление в запрос фильтра по адресу
     *
     * @param QueryBuilder $query_builder
     * @param $address
     * @return mixed
     */
    private function applyFilterByAddress($query_builder, $address)
    {
        $address = FilterTransformer::createFreeFilter($address);

        return $query_builder
            ->andWhere('LOWER(b.address) LIKE LOWER(:address)')
            ->setParameter('address', $address);
    }

    /**
     * Добавление в запрос фильтра по местоположению
     *
     * @param QueryBuilder $query_builder
     * @param float $latitude
     * @param float $longitude
     * @param int $radius
     * @return QueryBuilder
     */
    private function applyFilterByLocation($query_builder, $latitude, $longitude, $radius)
    {
        return $query_builder
            ->andWhere('GeoDistance(GeoPoint(b.longitude, b.latitude), GeoPoint(:longitude, :latitude)) < :radius')
            ->setParameter('longitude', $longitude)
            ->setParameter('latitude', $latitude)
            ->setParameter('radius', $radius);

    }

    /**
     * {@inheritDoc}
     */
    protected function applyParameters($query_builder, $params)
    {
        if (array_key_exists(SearchFilters::Q_ADDRESS, $params)) {
            $query_builder = $this->applyFilterByAddress($query_builder, $params[SearchFilters::Q_ADDRESS]);
        }

        if (array_key_exists(SearchFilters::Q_LOCATION, $params)) {
            if (!preg_match('/(?<latitude>[\d|\.]+),(?<longitude>[\d|\.]+)(,(?<radius>[\d|\.]+))?/ui',
                    $params[SearchFilters::Q_LOCATION], $match)) {
                throw new WrongParametersException(
                    'Неверное значение области поиска, доллжно быть "latitude,longitude,radius" получено "'.
                    $params[SearchFilters::Q_LOCATION].'"');
            }
            $query_builder = $this->applyFilterByLocation($query_builder,
                $match['latitude'], $match['longitude'], isset($match['radius']) ? $match['radius'] : 50);
        }

        return $query_builder;
    }

    /**
     * Поиск здания по идентификатору
     *
     * @param int $id
     * @return Building[]
     */
    public function findById($id)
    {
        return $this->getResult($this->getQueryBuilder()
            ->where('b.id = :id')
            ->setParameter('id', $id)
        );
    }

    /**
     * Поиск всех зданий с адресом удовлетворяющим заданному фильтру.
     * Фильтр строится из указанной строки $address удалением всех спецсимволов и заменой пробелов на
     * вхождение либого кол-ва любых символов.
     * Т.е. адрес "Советская, 7" будет рассматреваться как фильтр "%советская%7%"
     *
     * @param string $address
     * @return Building[]
     */
    public function findByAddress($address)
    {
        return $this->getResult(
            $this->applyFilterByAddress($this->getQueryBuilder(), $address)
        );
    }

    /**
     * Поиск зданий находящихся на заданном расстоянии от указанной точки
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $radius
     * @return Building[]
     */
    public function findByDistance($latitude, $longitude, $radius)
    {
        return $this->getResult(
            $this->applyFilterByLocation($this->getQueryBuilder(), $latitude, $longitude, $radius)
        );
    }
}