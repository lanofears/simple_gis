<?php

namespace AppBundle\Controller;

use AppBundle\Exception\EmptyResultException;
use AppBundle\Exception\WrongParametersException;
use AppBundle\Extensions\References\SearchFilters;
use AppBundle\Repository\Paginator\Paginator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Базовый класс контроллера
 *
 * @author Aleksey Skryzhavskiy
 */
class ApiController
{
    /**
     * Фильтрация входных параметров запроса
     *
     * @param Request $request
     * @param array $param_names
     * @return array
     * @throws WrongParametersException
     */
    protected function filterParams($request, $param_names)
    {
        $params = [];
        $wrong_params = [];
        $param_names[] = SearchFilters::Q_CALLBACK;

        foreach ($request->query->all() as $param_name => $param) {
            if (in_array($param_name, $param_names)) {
                $params[$param_name] = $param;
            }
            else {
                $wrong_params[] = $param_name;
            }
        }

        if ($wrong_params) {
            throw new WrongParametersException('Указаны неверные параметры запроса. '.
                'Данные параметры не поддерживаются: '.implode(',', $wrong_params));
        }

        return $params;
    }

    /**
     * Выдача данных пользователю
     *
     * @param string $header
     * @param Paginator|array $data
     * @return array
     * @throws EmptyResultException
     */
    protected function returnResult($header, $data)
    {
        $result = [ 'success' => true, 'code' => 200];

        if ($data instanceof Paginator) {
            $result['total'] = $data->count();
            $result[$header] = $data->getResult();
        }
        else {
            $result[$header] = $data;
        }

        if (!$result[$header]) {
            throw new EmptyResultException('Не найдено данных удовлетворяющих заданным критериям');
        }

        return $result;
    }
}