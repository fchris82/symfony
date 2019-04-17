<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter\Visitor;

use Symfony\Component\Console\Formatter\Token\DecorationToken;
use Symfony\Component\Console\Formatter\Token\EosToken;
use Symfony\Component\Console\Formatter\Token\FullTagToken;
use Symfony\Component\Console\Formatter\Token\FullTextToken;
use Symfony\Component\Console\Formatter\Token\SeparatorToken;
use Symfony\Component\Console\Formatter\Token\TagToken;
use Symfony\Component\Console\Formatter\Token\WordToken;

/**
 * Visitor for handling <href> tags!
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class HrefVisitor extends AbstractVisitor implements DecoratorVisitorInterface
{
    const START = "\e]8;;";
    const CLOSE = "\e\\";

    public function visitFullText(FullTextToken $fullTextToken): void
    {
        if ($this->handlesHrefGracefully()) {
            parent::visitFullText($fullTextToken);
        }
    }

    protected function handlesHrefGracefully()
    {
        return !\in_array(getenv('TERMINAL_EMULATOR'), ['JetBrains-JediTerm']);
    }

    public function visitSeparator(SeparatorToken $separatorToken): void
    {
        // do nothing
    }

    public function visitWord(WordToken $wordToken): void
    {
        // do nothing
    }

    public function visitTag(TagToken $tagToken): void
    {
        if ('href' == $tagToken->getName()) {
            if ($tagToken->getParent()->isStartTag()) {
                $tagToken->getParent()->insertAfter(new DecorationToken(sprintf(
                    '%s%s%s',
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

    public function visitEos(EosToken $eosToken): void
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

    public function visitDecoration(DecorationToken $decorationToken): void
    {
        // do nothing
    }
}
