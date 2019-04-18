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

use Symfony\Component\Console\Formatter\Lexer;
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

    public function iterate(iterable $tokens)
    {
        if ($this->handlesHrefGracefully()) {
            parent::iterate($tokens);
        }
    }

    protected function handlesHrefGracefully()
    {
        return !\in_array(getenv('TERMINAL_EMULATOR'), ['JetBrains-JediTerm']);
    }

    public function visitDecoration(DecorationToken $decorationToken): void
    {
        // do nothing
    }

    protected function handleWord(string $value)
    {
        // do nothing
    }

    protected function handleSeparator(string $value)
    {
        // do nothing
    }

    protected function handleTag(TagToken $tagToken)
    {
        if ('href' == $tagToken->getName()) {
            if ($tagToken->getParent()->isStartTag()) {
                $this->insertItem($this->i+1, [Lexer::TYPE_DECORATION, sprintf(
                    '%s%s%s',
                    self::START,
                    implode(',', $tagToken->getValues()),
                    self::CLOSE
                )]);
            }
            if ($tagToken->getParent()->isCloseTag()) {
                $this->insertItem($this->i, [Lexer::TYPE_DECORATION, self::START.self::CLOSE]);
            }
        }
    }

    protected function handleEos(EosToken $eosToken)
    {
        /** @var FullTagToken $unclosedTag */
        while ($unclosedTag = array_pop($this->tagStack)) {
            /** @var TagToken $tag */
            foreach ($unclosedTag->getIterator() as $tag) {
                if ('href' == $tag->getName()) {
                    $this->insertItem($this->i, [Lexer::TYPE_DECORATION, self::START.self::CLOSE]);
                }
            }
        }
    }

    protected function handleDecoration(string $value)
    {
        // do nothing
    }
}
