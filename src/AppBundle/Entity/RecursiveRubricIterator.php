<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use RecursiveIterator;

class RecursiveRubricIterator implements RecursiveIterator
{
    private $data;

    /**
     * RecursiveRubricIterator constructor.
     * @param $data
     */
    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->data->current();
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        return $this->data->next();
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->data->key();
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return ($this->data->current() instanceof Rubric);
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->data->first();
    }

    /**
     * {@inheritDoc}
     */
    public function hasChildren()
    {
        return (!$this->data->current()->getChildren()->isEmpty());
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren()
    {
        return new RecursiveRubricIterator($this->data->current()->getChildren());
    }
}