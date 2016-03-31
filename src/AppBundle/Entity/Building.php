<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;

/**
 * Информация о конретном здании - адрес, координаты, список организаций
 *
 * @author Aleksey Skryazhevskiy
 *
 * @ORM\Table(schema="gis_catalog", name="building")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BuildingRepository")
 * @ExclusionPolicy("all")
 */
class Building {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"list","details"})
     * @Expose
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Groups({"list","details"})
     * @Expose
     */
    protected $address;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", scale=8)
     * @Groups({"list","details"})
     * @Expose
     */
    protected $longitude;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", scale=8)
     * @Groups({"list","details"})
     * @Expose
     */
    protected $latitude;

    /**
     * @var Organization[]
     * @ORM\OneToMany(targetEntity="Organization", mappedBy="building")
     * @Groups({"building_details"})
     * @Expose
     */
    protected $organizations;

    /**
     * Конструктор класса зданий
     */
    public function __construct()
    {
        $this->organizations = new ArrayCollection();
    }

    /**
     * Идентификатор здания
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Установить адрес здания
     *
     * @param string $address
     * @return Building
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Адрес здания
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Установить координаты здания (долгота)
     *
     * @param float $longitude
     * @return Building
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * координаты здания (долгота)
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Установить координаты здания (широта)
     *
     * @param string $latitude
     * @return Building
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * координаты здания (широта)
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Установить координаты здания (широта, долгота)
     *
     * @param $latitude
     * @param $longitude
     * @return Building
     */
    public function setCoordinates($latitude, $longitude) {
        $this->latitude = $latitude;
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Добавить указанную организацию в здание
     *
     * @param Organization $organization
     * @return Building
     */
    public function addOrganization(Organization $organization)
    {
        $this->organizations[] = $organization;

        return $this;
    }

    /**
     * Удалить указанную организацию из здания
     *
     * @param Organization $organization
     */
    public function removeOrganization(Organization $organization)
    {
        $this->organizations->removeElement($organization);
    }

    /**
     * Список организация в здании
     *
     * @return Collection
     */
    public function getOrganizations()
    {
        return $this->organizations;
    }
}
