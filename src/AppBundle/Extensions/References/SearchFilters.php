<?php

namespace AppBundle\Extensions\References;

/**
 * Список констант определяющих список параметров запроса
 *
 * @author Aleksey Skryzhevskiy
 */
interface SearchFilters
{
    const Q_ADDRESS = 'address';
    const Q_LOCATION = 'location';
    const Q_RUBRIC = 'rubric';
    const Q_ORDER = 'order';
    const Q_NAME = 'name';
    const Q_PARENT = 'parent';
    const Q_CALLBACK = 'callback';
    const Q_LIMIT = 'limit';
    const Q_PAGE = 'page';
}