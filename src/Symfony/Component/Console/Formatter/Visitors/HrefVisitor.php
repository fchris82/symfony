<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.03.
 * Time: 19:17
 */

namespace Symfony\Component\Console\Formatter\Visitors;

use Symfony\Component\Console\Formatter\Tokens\DecorationToken;
use Symfony\Component\Console\Formatter\Tokens\EosToken;
use Symfony\Component\Console\Formatter\Tokens\FullTagToken;
use Symfony\Component\Console\Formatter\Tokens\FullTextToken;
use Symfony\Component\Console\Formatter\Tokens\SeparatorToken;
use Symfony\Component\Console\Formatter\Tokens\TagToken;
use Symfony\Component\Console\Formatter\Tokens\WordToken;

class HrefVisitor extends AbstractVisitor
{
    const START = "\e]8;;";
    const CLOSE = "\e\\";

    protected $hrefTagStack = [];

    public function visitFullText(FullTextToken $fullTextToken)
    {
        if ($this->handlesHrefGracefully()) {
            parent::visitFullText($fullTextToken);
        }
    }

    protected function handlesHrefGracefully()
    {
        return !in_array(getenv('TERMINAL_EMULATOR'), ['JetBrains-JediTerm']);
    }

    public function visitSeparator(SeparatorToken $separatorToken)
    {
        // do nothing
    }

    public function visitWord(WordToken $wordToken)
    {
        // do nothing
    }

    public function visitTag(TagToken $tagToken)
    {
        if ('href' == $tagToken->getName()) {
            array_push($this->hrefTagStack, $this->tagDepth);
            $tagToken->getParent()->insertAfter(new DecorationToken(sprintf(
                "%s%s%s",
                self::START,
                implode(',', $tagToken->getValues()),
                self::CLOSE
            )));
        } elseif ($tagToken->getParent()->isCloseTag() && end($this->hrefTagStack) == $this->tagDepth) {
            $tagToken->getParent()->insertBefore(new DecorationToken(self::START.self::CLOSE));
            array_pop($this->hrefTagStack);
        }
    }

    public function visitEos(EosToken $eosToken)
    {
        while (array_pop($this->hrefTagStack)) {
            $eosToken->insertBefore(new DecorationToken(self::START.self::CLOSE));
        }
    }

    public function visitDecoration(DecorationToken $decorationToken)
    {
        // do nothing
    }
}
