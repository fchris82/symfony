<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.03.
 * Time: 20:01
 */

namespace Symfony\Component\Console\Formatter\Visitors;


use Symfony\Component\Console\Formatter\Tokens\FullTagToken;
use Symfony\Component\Console\Formatter\Tokens\FullTextToken;

abstract class AbstractVisitor implements FormatterVisitorInterface
{
    protected $tagDepth = 0;

    public function visitFullText(FullTextToken $fullTextToken)
    {
        $this->tagDepth = 0;
        $iterator = $fullTextToken->getIterator();
        for ($iterator->rewind();$iterator->valid();$iterator->next()) {
            $iterator->current()->accept($this);
        }
    }

    public function visitFullTagToken(FullTagToken $fullTagToken)
    {
        if ($fullTagToken->isStartTag()) {
            $this->tagDepth++;
        }
        $iterator = $fullTagToken->getIterator();
        for ($iterator->rewind();$iterator->valid();$iterator->next()) {
            $iterator->current()->accept($this);
        }
        if ($fullTagToken->isCloseTag()) {
            $this->tagDepth--;
        }
    }
}
