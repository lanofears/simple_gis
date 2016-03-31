<?php

namespace AppBundle\Extensions\Utils;


class FilterTransformer
{
    public static function createFreeFilter($string)
    {
        return preg_replace('/(^|\W+|$)/ui', '%', $string);
    }

    public static function createSubStringFilter($string)
    {
        return '%'.$string.'%';
    }
}