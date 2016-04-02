<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\AccessorOrder;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * Рубрика, позволяющая класифицировать род деятельности организаций
 *
 * @author Aleksey Skryazhevskiy
 *
 * @ORM\Table(schema="gis_catalog", name="rubric")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RubricRepository")
 * @ExclusionPolicy("all")
 * @AccessorOrder("custom", custom = {"id", "parent_id", "name", "nestedChildren"})
 */
class Rubric
{
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
     * @var int
     *
     * @ORM\Column(type="integer")
     * @Groups({"list","details"})
     * @Expose
     */
    protected $parent_id;

    /**
     * @var Rubric
     *
     * @ORM\ManyToOne(targetEntity="Rubric", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parent;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Groups({"list","details"})
     * @Expose
     */
    protected $name;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Rubric", mappedBy="parent")
     */
    protected $children;

    /**
     * @VirtualProperty
     * @Type("array")
     * @SerializedName("children")
     * @Groups({"rubric_details"})
     */
    public function nestedChildren()
    {
        $children = [];
        /** @var Rubric $child */
        foreach ($this->children as $child) {
            $children[] = [ 'id' => $child->id, 'name' => $child->name ];
        }
        return $children;
    }

    /**
     * Конструткор класса рубрики
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * Идентификатор рубрики
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Установить наименование рубрики
     *
     * @param string $name
     * @return Rubric
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Наименование рубрики
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Устанвоить родителькую рубрику (пусто, если рубрика является верхнеуровневой)
     *
     * @param Rubric $parent
     * @return Rubric
     */
    public function setParent(Rubric $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Родительская рубрика
     *
     * @return Rubric
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Добавить дочернюю рубрику
     *
     * @param Rubric $child
     * @return Rubric
     */
    public function addChild(Rubric $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Удалить дочернюю рубрику
     *
     * @param Rubric $child
     */
    public function removeChild(Rubric $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Список дочерних рубрик
     *
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
    }
}
