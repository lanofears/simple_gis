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
     * {@inheritDoc}
     */
    protected function applyParameters($query_builder, $params)
    {
        if (array_key_exists(SearchFilters::Q_ADDRESS, $params)) {
            $address = FilterTransformer::createFreeFilter($params[SearchFilters::Q_ADDRESS]);
            $query_builder
                ->andWhere('b.address LIKE :address')
                ->setParameter('address', $address);
        }

        if (array_key_exists(SearchFilters::Q_LOCATION, $params)) {
            if (!preg_match('/(?<latitude>[\d|\.]+),(?<longitude>[\d|\.]+)(,(?<radius>[\d|\.]+))?/ui',
                    $params[SearchFilters::Q_LOCATION], $match)) {
                throw new WrongParametersException(
                    'Неверное значение области поиска, доллжно быть "latitude,longitude,radius" получено "'.
                    $params[SearchFilters::Q_LOCATION].'"');
            }
            $query_builder
                ->andWhere('GeoDistance(GeoPoint(b.longitude, b.latitude), GeoPoint(:longitude, :latitude)) < :radius')
                ->setParameter('longitude', $match['longitude'])
                ->setParameter('latitude', $match['latitude'])
                ->setParameter('radius', isset($match['radius']) ? $match['radius'] : 50);
        }

        return $query_builder;
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
        $address = FilterTransformer::createFreeFilter($address);
        return $this->getResult($this->getQueryBuilder()
            ->where('LOWER(b.address) LIKE LOWER(:address)')
            ->setParameter('address', $address)
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
        return $this->getResult($this->getQueryBuilder()
            ->where('GeoDistance(GeoPoint(b.longitude, b.latitude), GeoPoint(:longitude, :latitude)) < :radius')
            ->setParameter('longitude', $longitude)
            ->setParameter('latitude', $latitude)
            ->setParameter('radius', $radius)
        );
    }
}