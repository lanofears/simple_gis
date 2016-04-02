<?php

namespace AppBundle\Extensions\Utils;

/**
 * Утилита для проверки массивов
 *
 * @author Aleksey Skryazhevskiy
 */
class ArrayValidator
{
    /**
     * Проверяет являются ли заначения одного массива подмножеством значений другого
     *
     * @param array $subset
     * @param array $set
     * @return bool
     */
    public static function isSubsetOf($subset, $set)
    {
        foreach ($subset as $key) {
            if (!array_key_exists($key, $set)) {
                return false;
            }
        }

        return true;
    }
}