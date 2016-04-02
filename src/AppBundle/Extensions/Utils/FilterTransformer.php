<?php

namespace AppBundle\Extensions\Utils;

/**
 * Утилита для форматирования строковых фильтров
 *
 * @author Aleksey Skryazhevskiy
 */
class FilterTransformer
{
    /**
     * Замена всех не букво-циферных символов на %, а так же добавление % в начало и конец строки
     *
     * @param string $string
     * @return string
     */
    public static function createFreeFilter($string)
    {
        return preg_replace('/(^|\W+|$)/ui', '%', $string);
    }

    /**
     * Добавление % в начало и конец строки
     *
     * @param string $string
     * @return string
     */
    public static function createSubStringFilter($string)
    {
        return '%'.$string.'%';
    }
}