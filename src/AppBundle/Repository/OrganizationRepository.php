<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Building;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Rubric;
use AppBundle\Exception\WrongParametersException;
use AppBundle\Extensions\References\SearchFilters;
use AppBundle\Extensions\Utils\ArrayValidator;
use AppBundle\Extensions\Utils\FilterTransformer;
use Doctrine\ORM\QueryBuilder;

/**
 * Репозиторий для работы с организациями
 *
 * @author Aleksey Skryazhevskiy
 */
class OrganizationRepository extends AbstractRepository
{
    const ORDER_BY_NAME = 'name';
    const ORDER_BY_ADDRESS = 'address';

    protected static $order_mapping = [
        self::ORDER_BY_NAME     => 'o.name',
        self::ORDER_BY_ADDRESS  => 'b.address'
    ];

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
     * @param QueryBuilder $query_builder
     * @param int $rubric
     * @param bool $recursive
     * @return QueryBuilder
     */
    private function applyFilterByRubric($query_builder, $rubric, $recursive = false)
    {
        if ($recursive) {
            $rubric_ids = array_keys($this->getEntityManager()
                ->getRepository('AppBundle:Rubric')
                ->findByIdRecursive($rubric));
            if ($rubric_ids) {
                return $query_builder
                    ->andWhere('r.id IN (:rubric)')
                    ->setParameter('rubric', $rubric_ids);
            }
        }

        return $query_builder
            ->andWhere('r.id = :rubric')
            ->setParameter('rubric', $rubric);
    }

    /**
     * {@inheritDoc}
     */
    protected function applyParameters($query_builder, $params)
    {
        if (array_key_exists(SearchFilters::Q_ADDRESS, $params)) {
            $query_builder = $this->applyFilterByAddress($query_builder, $params[SearchFilters::Q_ADDRESS]);
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
            $query_builder = $this->applyFilterByLocation($query_builder,
                $match['latitude'], $match['longitude'], isset($match['radius']) ? $match['radius'] : 50);
        }

        if (array_key_exists(SearchFilters::Q_RUBRIC, $params)) {
            if (!preg_match('/(?<rubric>\d+)(?<recursive>,recursive)?/ui', $params[SearchFilters::Q_RUBRIC], $match)) {
                throw new WrongParametersException('Нверное значения фильтра по раубрике, должно быть '.
                    '"rubric_id[,recursive]", получено "'.$params[SearchFilters::Q_RUBRIC].'"');
            }
            $query_builder = $this->applyFilterByRubric($query_builder, $match['rubric'],isset($match['recursive']));
        }

        $sort = [ self::ORDER_BY_NAME ];
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

    /**
     * Поиск организации по идентификатору
     *
     * @param int $id
     * @return Organization[]
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
     * @param bool $recursive
     * @return Organization[]
     */
    public function findByRubric($rubric, $recursive = false)
    {
        $rubric = ($rubric instanceof Rubric) ? $rubric->getId() : $rubric;

        return $this->getResult(
            $this->applyFilterByRubric($this->getQueryBuilder(), $rubric, $recursive)
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
        return $this->getResult(
            $this->applyFilterByAddress($this->getQueryBuilder(), $address)
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
        return $this->getResult(
            $this->applyFilterByLocation($this->getQueryBuilder(), $latitude, $longitude, $radius)
        );
    }
}
