<?php

namespace AppBundle\Extensions\Utils;

/**
 * Утилита для проверки целочисленных значений
 *
 * @author Aleksey Skryazhevskiy
 */
class NumericValidator
{
    /**
     * Проверка на значения на то, что оно является целым числом
     * Дополнительно можно установить границы значения целого числа
     *
     * @param mixed $value
     * @param int|null $min
     * @param int|null $max
     * @return bool
     */
    public static function isIntConstraint($value, $min = null, $max = null)
    {
        if ((string)$value != (string)intval($value)) {
            return false;
        }

        if (!is_null($min) && ($value < $min)) {
            return false;
        }

        if (!is_null($max) && ($value > $max)) {
            return false;
        }

        return true;
    }
}