<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;

/**
 * Информация об организации (наименование, телефоны, адрес размещения, сфера деятельности)
 *
 * @author Aleksey Skryazhevskiy
 *
 * @ORM\Table(name="organization", schema="gis_catalog")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OrganizationRepository")
 * @ExclusionPolicy("all")
 */
class Organization
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"list","details"})
     * @Expose
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Groups({"list","details"})
     * @Expose
     */
    protected $name;

    /**
     * @var array
     *
     * @ORM\Column(name="phones", type="simple_array", nullable=true)
     * @Groups({"list","details"})
     * @Expose
     */
    protected $phones;

    /**
     * @var Building
     *
     * @ORM\ManyToOne(targetEntity="Building", inversedBy="organizations")
     * @ORM\JoinColumn(name="building_id", referencedColumnName="id")
     * @Groups({"list","organization_details"})
     * @MaxDepth(1)
     * @Expose
     */
    protected $building;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="Rubric")
     * @ORM\JoinTable(schema="gis_catalog", name="organization_rubrics",
     *      joinColumns={@ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="rubric_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @Groups({"list","details"})
     * @Expose
     */
    protected $rubrics;

    /**
     * Конструктор класса организаций
     */
    public function __construct()
    {
        $this->rubrics = new ArrayCollection();
    }

    /**
     * Идентификатор организации
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Установить наименование организации
     *
     * @param string $name
     * @return Organization
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Наименование организации
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Установить список телефонов
     *
     * @param array $phones
     * @return Organization
     */
    public function setPhones($phones)
    {
        $this->phones = $phones;

        return $this;
    }

    /**
     * Добавить новый телефон в список телефонов.
     * Если такой телефон уже есть в списке, то он не будет добавлен
     *
     * @param array $phone
     * @return Organization
     */
    public function addPhone($phone)
    {
        if (!$this->phones || !in_array($phone, $this->phones)) {
            $this->phones = $this->phones ? $this->phones : [];
            $this->phones[] = $phone;
        }

        return $this;
    }

    /**
     * Удалить телефон из список телефонов.
     * Если такой телефон отсутствует в списке, то операция будет проигнорирована
     *
     * @param array $phone
     * @return Organization
     */
    public function removePhone($phone)
    {
        if ($this->phones && ($key = array_search($phone, $this->phones))) {
            unset($this->phones[$key]);
        }
    }

    /**
     * Список телефонов
     *
     * @return array
     */
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * Установить здание в котором размещена орагнизация
     *
     * @param Building $building
     * @return Organization
     */
    public function setBuilding(Building $building = null)
    {
        $this->building = $building;

        return $this;
    }

    /**
     * Здание в котором размещена орагнизация
     *
     * @return Building
     */
    public function getBuilding()
    {
        return $this->building;
    }

    /**
     * Добавить рубрику
     *
     * @param Rubric $rubric
     * @return Organization
     */
    public function addRubric(Rubric $rubric)
    {
        $this->rubrics[] = $rubric;

        return $this;
    }

    /**
     * Удалить рубрику
     *
     * @param Rubric $rubric
     */
    public function removeRubric(Rubric $rubric)
    {
        $this->rubrics->removeElement($rubric);
    }

    /**
     * Список рубрик
     *
     * @return Collection
     */
    public function getRubrics()
    {
        return $this->rubrics;
    }
}
