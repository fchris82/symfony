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

use Symfony\Component\Console\Formatter\Token\FullTagToken;
use Symfony\Component\Console\Formatter\Token\FullTextToken;
use Symfony\Component\Console\Formatter\Token\TagToken;

/**
 * Base visitor.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
abstract class AbstractVisitor implements FormatterVisitorInterface
{
    /** @var array|FullTagToken[] */
    protected $tagStack = [];

    /**
     * {@inheritdoc}
     */
    public function visitFullText(FullTextToken $fullTextToken): void
    {
        $this->tagStack = [];
        $iterator = $fullTextToken->getIterator();
        for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {
            $iterator->current()->accept($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function visitFullTagToken(FullTagToken $fullTagToken): void
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
            $item->accept($this);
        }
    }
}
