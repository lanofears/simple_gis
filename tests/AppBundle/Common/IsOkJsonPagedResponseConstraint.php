<?php

namespace Tests\AppBundle\Common;

use PHPUnit_Framework_Constraint;
use Symfony\Component\HttpFoundation\Response;

/**
 * Проверка на соответствие корректному ответу на запрос к API, содержащий постраничные данные
 *
 * @author Aleksey Skryzhavskiy
 */
class IsOkJsonPagedResponseConstraint extends PHPUnit_Framework_Constraint
{
    /**
     * @param Response $other
     * @return bool
     */
    protected function matches($other)
    {
        if ($other->getStatusCode() != Response::HTTP_OK) {
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

        if (!array_key_exists('code', $other) || ($other['code'] != Response::HTTP_OK)) {
            return false;
        }

        if ($other['success'] !== true) {
            return false;
        }

        if (!array_key_exists('total', $other) || ($other['total'] < 1)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function toString()
    {
        return 'корректный ответ на запрос к API, содержащий постраничные данные';
    }
}