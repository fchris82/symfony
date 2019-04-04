<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.03.
 * Time: 19:17
 */

namespace Symfony\Component\Console\Formatter\Visitors;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\Tokens\DecorationToken;
use Symfony\Component\Console\Formatter\Tokens\EosToken;
use Symfony\Component\Console\Formatter\Tokens\FullTagToken;
use Symfony\Component\Console\Formatter\Tokens\FullTextToken;
use Symfony\Component\Console\Formatter\Tokens\SeparatorToken;
use Symfony\Component\Console\Formatter\Tokens\TagToken;
use Symfony\Component\Console\Formatter\Tokens\WordToken;

class HrefVisitor extends AbstractVisitor implements DecoratorVisitorInterface
{
    const START = "\e]8;;";
    const CLOSE = "\e\\";

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
            if ($tagToken->getParent()->isStartTag()) {
                $tagToken->getParent()->insertAfter(new DecorationToken(sprintf(
                    "%s%s%s",
                    self::START,
                    implode(',', $tagToken->getValues()),
                    self::CLOSE
                )));
            }
            if ($tagToken->getParent()->isCloseTag()) {
                $tagToken->getParent()->insertBefore(new DecorationToken(self::START.self::CLOSE));
            }
        }
    }

    public function visitEos(EosToken $eosToken)
    {
        /** @var FullTagToken $unclosedTag */
        while ($unclosedTag = array_pop($this->tagStack)) {
            /** @var TagToken $tag */
            foreach ($unclosedTag->getIterator() as $tag) {
                if ('href' == $tag->getName()) {
                    $eosToken->insertBefore(new DecorationToken(self::START.self::CLOSE));
                }
            }
        }
    }

    public function visitDecoration(DecorationToken $decorationToken)
    {
        // do nothing
    }
}
