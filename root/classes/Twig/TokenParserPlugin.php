<?php
namespace eGamings\WLC\Twig;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class TokenParserPlugin extends AbstractTokenParser
{
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $value = $this->parser->getStream()->expect(Token::STRING_TYPE)->getValue();
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new NodePlugin($value, $lineno);
    }

    public function getTag()
    {
        return 'plugin';
    }
}