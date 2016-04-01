<?php

namespace Tests\AppBundle\Common;

use \PHPUnit_Framework_Constraint;
use Symfony\Component\HttpFoundation\Response;

class IsErrorJsonConstraint extends PHPUnit_Framework_Constraint
{
    protected $code;

    /**
     * Конструктор класса
     *
     * @param $code
     */
    public function __construct($code)
    {
        parent::__construct();
        $this->code = $code;
    }

    /**
     * @param Response $other
     * @return bool
     */
    protected function matches($other)
    {
        if ($other->getStatusCode() != $this->code) {
            return false;
        }

        $other = $other->getContent();
        if (!is_string($other)) {
            return false;
        }

        $other = json_decode($other, true);
        if ($other === null) {
            return false;
        }

        if (!is_array($other)) {
            return false;
        }

        if (!array_key_exists('success', $other)) {
            return false;
        }

        if (!array_key_exists('code', $other) || ($other['code'] != $this->code)) {
            return false;
        }

        if (!array_key_exists('message', $other) || !strlen($other['message'])) {
            return false;
        }

        if ($other['success'] !== false) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function toString()
    {
        return 'ответ является корректной ошибкой в формате JSON';
    }
}