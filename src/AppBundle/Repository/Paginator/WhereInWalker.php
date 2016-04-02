<?php

namespace AppBundle\Repository\Paginator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\InExpression;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\NullComparisonExpression;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use RuntimeException;

/**
 * Модифицированный вариант класса из пакета DoctrineExtensions.
 *
 * В оригинальном варианте услование WHERE IN не зменяло текущие условия, а дополняло их.
 * Что заставляло СУБД после отбора записей по ID дополнительно фильтровать их по начальным условиям
 * исходного запроса.
 */
class WhereInWalker extends TreeWalkerAdapter
{
    const HINT_PAGINATOR_ID_COUNT = 'doctrine.id.count';
    const PAGINATOR_ID_ALIAS = 'dpid';

    /**
     * Полностью заменяет все текущие условия запроса на WHERE id IN (...)
     *
     * @param SelectStatement $AST
     * @return void
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        $queryComponents = $this->_getQueryComponents();
        $from = $AST->fromClause->identificationVariableDeclarations;

        if (count($from) > 1) {
            throw new RuntimeException("Cannot count query which selects two FROM components, cannot make distinction");
        }

        $fromRoot            = reset($from);
        $rootAlias           = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        /** @var ClassMetadata $rootClass */
        $rootClass           = $queryComponents[$rootAlias]['metadata'];
        $identifierFieldName = $rootClass->getSingleIdentifierFieldName();

        $pathType = PathExpression::TYPE_STATE_FIELD;
        if (isset($rootClass->associationMappings[$identifierFieldName])) {
            $pathType = PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
        }

        $pathExpression       = new PathExpression(PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION, $rootAlias, $identifierFieldName);
        $pathExpression->type = $pathType;

        $count = $this->_getQuery()->getHint(self::HINT_PAGINATOR_ID_COUNT);

        if ($count > 0) {
            $arithmeticExpression = new ArithmeticExpression();
            $arithmeticExpression->simpleArithmeticExpression = new SimpleArithmeticExpression(
                array($pathExpression)
            );
            $expression = new InExpression($arithmeticExpression);
            $expression->literals[] = new InputParameter(":" . self::PAGINATOR_ID_ALIAS);

        } else {
            $expression = new NullComparisonExpression($pathExpression);
            $expression->not = false;
        }

        $conditionalPrimary = new ConditionalPrimary;
        $conditionalPrimary->simpleConditionalExpression = $expression;
        $AST->whereClause = new WhereClause(
            new ConditionalExpression([
                new ConditionalTerm([
                    $conditionalPrimary
                ])
            ])
        );
    }
}
