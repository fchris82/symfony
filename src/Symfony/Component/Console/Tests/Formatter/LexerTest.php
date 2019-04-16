<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\Formatter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\Lexer;

class LexerTest extends TestCase
{
    /**
     * @dataProvider dpTokenize
     */
    public function testTokenize($text, $tokenContentAsString)
    {
        $lexer = new Lexer();
        $response = $lexer->tokenize($text);
        $this->assertEquals(sprintf("FullTextToken(\n%s\n)", $tokenContentAsString), (string) $response);
    }

    public function dpTokenize()
    {
        return [
            ['', 'EosToken()'],
            ['0', "WordToken(0)\nEosToken()"],
            ['word', "WordToken(word)\nEosToken()"],
            ['<tag>', "FullTagToken(TagToken<tag>)\nEosToken()"],
            ['<tag/>', "FullTagToken(TagToken<tag/>)\nEosToken()"],
            ['\<tag>', "WordToken(<tag>)\nEosToken()"],
            ['< <tag> >', "WordToken(<)\nSeparatorToken( )\nFullTagToken(TagToken<tag>)\nSeparatorToken( )\nWordToken(>)\nEosToken()"],
            ['word1 <tag1;tag2=att1,att2>word2</>', <<<EOS
WordToken(word1)
SeparatorToken( )
FullTagToken(TagToken<tag1>+TagToken<tag2=att1,att2>)
WordToken(word2)
FullTagToken()
EosToken()
EOS
            ],
        ];
    }
}
