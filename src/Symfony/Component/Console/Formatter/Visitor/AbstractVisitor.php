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

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\Lexer;
use Symfony\Component\Console\Formatter\Token\EosToken;
use Symfony\Component\Console\Formatter\Token\FullTagToken;
use Symfony\Component\Console\Formatter\Token\FullTextToken;
use Symfony\Component\Console\Formatter\Token\TagToken;
use Symfony\Component\Console\Formatter\Token\Token;

/**
 * Base visitor.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
abstract class AbstractVisitor implements FormatterVisitorInterface
{
    /** @var iterable */
    protected $tokens;

    /** @var int */
    protected $i;

    /** @var array|FullTagToken[] */
    protected $tagStack = [];

    public function iterate(iterable $tokens)
    {
        $this->tokens = $tokens;
        for ($this->i=0; $this->i<\count($this->tokens); $this->i++) {
            $token = $this->tokens[$this->i];
            if (is_array($token)) {
                list ($type, $value) = $token;
            } elseif ($token instanceof Token) {
                $type = get_class($token);
                $value = $token;
            } else {
                throw new InvalidArgumentException('????' . gettype($token));
            }

            $this->handleToken($type, $value);
        }
    }

    protected function handleToken($type, $value)
    {
        switch($type) {
            case Lexer::TYPE_WORD:
                $this->handleWord($value);
                break;
            case Lexer::TYPE_SEPARATOR:
                $this->handleSeparator($value);
                break;
            case Lexer::TYPE_DECORATION:
                $this->handleDecoration($value);
                break;
            case FullTagToken::class:
                $this->handleFullTagToken($value);
                break;
//            case TagToken::class:
//                $this->handleTag($value);
//                break;
            default:
                throw new InvalidArgumentException('?');
        }
    }

    protected function removeItem(int $i)
    {
        array_splice($this->tokens, $i, 1);
        if ($i <= $this->i) {
            $this->i--;
        }
    }

    protected function replaceItem(int $i, $newItem)
    {
        array_splice($this->tokens, $i, 1, [$newItem]);
    }

    protected function insertItem(int $i, $newItem)
    {
        array_splice($this->tokens, $i, 0, [$newItem]);
        if ($i <= $this->i) {
            $this->i++;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function handleFullTagToken(FullTagToken $fullTagToken): void
    {
        if ($fullTagToken->isStartTag()) {
            array_push($this->tagStack, $fullTagToken);
            $this->handleTags($fullTagToken);
        }
        if ($fullTagToken->isCloseTag()) {
            $lastOpenTag = end($this->tagStack);
            if (0 == $fullTagToken->getIterator()->count()) {
                foreach ($lastOpenTag->getIterator() as $item) {
                    $fullTagToken->push(clone $item);
                }
            }
            // If the tag is self closed we already handled it at "start"
            if (!$fullTagToken->isSelfClosed()) {
                $this->handleTags($fullTagToken);
            }
            array_pop($this->tagStack);
        }
    }

    /**
     * Accept each tag child.
     *
     * @param FullTagToken $fullTagToken
     */
    protected function handleTags(FullTagToken $fullTagToken): void
    {
        /** @var TagToken $item */
        foreach ($fullTagToken->getIterator() as $item) {
            $this->handleTag($item);
        }
    }

    protected abstract function handleWord(string $value);
    protected abstract function handleSeparator(string $value);
    protected abstract function handleTag(TagToken $tagToken);
    protected abstract function handleDecoration(string $value);
    protected abstract function handleEos(EosToken $eosToken);
}
