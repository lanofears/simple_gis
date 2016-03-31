<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Building;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Rubric;
use AppBundle\Exception\WrongParametersException;
use AppBundle\Extensions\References\SearchFilters;
use AppBundle\Extensions\Utils\FilterTransformer;

/**
 * Репозиторий для работы с организациями
 *
 * @author Aleksey Skryazhevskiy
 */
class OrganizationRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return $this->createQueryBuilder('o')
            ->select('o, r, b')
            ->leftJoin('o.building', 'b')
            ->leftJoin('o.rubrics', 'r');
    }

    /**
     * {@inheritDoc}
     */
    protected function applyParameters($query_builder, $params)
    {
        if (array_key_exists(SearchFilters::Q_ADDRESS, $params)) {
            $address = FilterTransformer::createFreeFilter($params[SearchFilters::Q_ADDRESS]);
            $query_builder
                ->andWhere('LOWER(b.address) LIKE LOWER(:address)')
                ->setParameter('address', $address);
        }

        if (array_key_exists(SearchFilters::Q_NAME, $params)) {
            $name = FilterTransformer::createSubStringFilter($params[SearchFilters::Q_NAME]);
            $query_builder
                ->andWhere('LOWER(o.name) LIKE LOWER(:name)')
                ->setParameter('name', $name);
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

        if (array_key_exists(SearchFilters::Q_RUBRIC, $params)) {
            if (!preg_match('/\(?<rubric>d+)(?<recursive>,recursive)?/ui', $params[SearchFilters::Q_RUBRIC], $match)) {
                throw new WrongParametersException('Нверное значения фильтра по раубрике, должно быть '.
                    '"rubric_id[,recursive]", получено "'.$params[SearchFilters::Q_RUBRIC].'"');
            }
            $rubric = $match['rubric'];

            if (isset($match['recursive'])) {
                $rubric_ids = array_keys($this->getEntityManager()
                    ->getRepository('AppBundle:Rubric')
                    ->findByIdRecursive($rubric));
                if (!$rubric_ids) {
                    return [ ];
                }

                $query_builder
                    ->andWhere('r.id IN (:rubric)')
                    ->setParameter('rubric', $rubric_ids);
            }
            else {
                $query_builder
                    ->andWhere('r.id = :rubric')
                    ->setParameter('rubric', $rubric);
            }
        }

        if (array_key_exists(SearchFilters::Q_ORDER, $params)) {
            $order_values = ['name' => 'o.name', 'address' => 'b.address'];
            foreach (explode(',', $params[SearchFilters::Q_ORDER]) as $order) {
                if (!array_key_exists($order, $order_values)) {
                    throw new WrongParametersException(
                        'Неверное значение поля для портировки, доллжно быть "name", или "address", получено "'.$order.'"');
                }

                $query_builder->addOrderBy($order_values[$order]);
            }
        }
        else {
            $query_builder->addOrderBy('o.name');
        }

        return $query_builder;
    }

    /**
     * Поиск организации по идентификатору
     *
     * @param int $id
     * @return Organization
     */
    public function findById($id)
    {
        return $this->getResult($this->getQueryBuilder()
            ->where('o.id = :id')
            ->setParameter('id', $id)
        );
    }

    /**
     * Поиск всех организаций с указанной рубрикой (по идентификатору рубрики)
     *
     * @param Rubric|int $rubric
     * @return Organization[]
     */
    public function findByRubric($rubric) {
        $rubric_ids = array_keys($this->getEntityManager()->getRepository('AppBundle:Rubric')->findByIdRecursive($rubric));
        if (!$rubric_ids) {
            return [];
        }

        return $this->getResult($this->getQueryBuilder()
            ->where('r.id IN (:rubric)')
            ->setParameter('rubric', $rubric_ids)
        );
    }

    /**
     * Поиск всех организаций с рубрикой содержащей в названии подстроку
     *
     * @param string $name
     * @return Organization[]
     */
    public function findByRubricName($name)
    {
        $name = FilterTransformer::createSubStringFilter($name);
        return $this->getResult($this->getQueryBuilder()
            ->where('LOWER(r.name) LIKE LOWER(:name)')
            ->setParameter('name', $name)
        );
    }

    /**
     * Поиск всех организаций в указанном здании (по идентификатору здания)
     *
     * @param Building|int $building
     * @return Organization[]
     */
    public function findByBuilding($building)
    {
        $building = ($building instanceof Building) ? $building->getId() : $building;
        return $this->getResult($this->getQueryBuilder()
            ->where('o.building = :building')
            ->setParameter('building', $building)
        );
    }

    /**
     * Поиск всех организаций с адресом удовлетворяющим заданному фильтру.
     * Фильтр строится из указанной строки $address удалением всех спецсимволов и заменой пробелов на
     * вхождение либого кол-ва любых символов.
     * Т.е. адрес "Советская, 7" будет рассматреваться как фильтр "%советская%7%"
     *
     * @param string $address
     * @return Organization[]
     */
    public function findByAddressPart($address)
    {
        $address = FilterTransformer::createFreeFilter($address);
        return $this->getResult($this->getQueryBuilder()
            ->where('LOWER(b.address) LIKE LOWER(:address)')
            ->setParameter('address', $address)
        );
    }

    /**
     * Поиск организаций находящихся на заданном расстоянии от указанной точки
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $radius
     * @return Organization[]
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
