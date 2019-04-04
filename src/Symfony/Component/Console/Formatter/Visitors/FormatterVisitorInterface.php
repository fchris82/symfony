<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.02.
 * Time: 15:43
 */

namespace Symfony\Component\Console\Formatter\Visitors;


use Symfony\Component\Console\Formatter\Tokens\FullTagToken;
use Symfony\Component\Console\Formatter\Tokens\DecorationToken;
use Symfony\Component\Console\Formatter\Tokens\EosToken;
use Symfony\Component\Console\Formatter\Tokens\FullTextToken;
use Symfony\Component\Console\Formatter\Tokens\SeparatorToken;
use Symfony\Component\Console\Formatter\Tokens\TagToken;
use Symfony\Component\Console\Formatter\Tokens\WordToken;

interface FormatterVisitorInterface
{
    public function visitFullText(FullTextToken $fullTextToken);
    public function visitSeparator(SeparatorToken $separatorToken);
    public function visitWord(WordToken $wordToken);
    public function visitFullTagToken(FullTagToken $fullTagToken);
    public function visitTag(TagToken $tagToken);
    public function visitEos(EosToken $eosToken);
    public function visitDecoration(DecorationToken $decorationToken);
}
