<?php

namespace AppBundle\Extensions\ORM\Functions;

use Doctrine\ORM\Query\AST\ArithmeticFactor;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Класс для поддержки PostGIS функции ST_Distance в DQL
 *
 * @author Aleksey Skryazhevskiy
 */
class GeoDistance extends FunctionNode
{
    /**
     * @var ArithmeticFactor[]
     */
    protected $expressions = [];

    /**
     * {@inheritDoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->expressions[] = $parser->ArithmeticFactor();

        $parser->match(Lexer::T_COMMA);

        $this->expressions[] = $parser->ArithmeticFactor();

        $lexer = $parser->getLexer();

        if ($lexer->lookahead['type'] === Lexer::T_COMMA) {
            $parser->match(Lexer::T_COMMA);
            $this->expressions[] = $parser->ArithmeticFactor();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritDoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $arguments = array();

        foreach ($this->expressions as $expression) {
            $arguments[] = $expression->dispatch($sqlWalker);
        }

        return 'ST_Distance(' . implode(', ', $arguments) . ')';
    }
}