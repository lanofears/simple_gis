<?php

namespace Tests\AppBundle\Common;

use PHPUnit_Framework_Constraint;

/**
 * Class IsValidResponseDataConstraint
 *
 * @author Aleksey Skryzhevskiy
 */
class IsValidResponseDataConstraint extends PHPUnit_Framework_Constraint
{
    protected $data_key;
    protected $object_fields;

    /**
     * Конструктор класса
     *
     * @param string $data_key
     * @param array $object_fields
     */
    public function __construct($data_key, $object_fields)
    {
        parent::__construct();
        $this->data_key = $data_key;
        $this->object_fields = $object_fields;
    }

    protected function matches($other)
    {
        if (!is_array($other)) {
            return false;
        }

        if (!array_key_exists($this->data_key, $other)) {
            return false;
        }

        if (!count($other[$this->data_key])) {
            return false;
        }

        foreach ($other[$this->data_key] as $object) {
            foreach ($this->object_fields as $field) {
                if (!array_key_exists($field, $object)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function toString()
    {
        return 'содержит объекты с корректной структурой';
    }
}