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
use Symfony\Component\Console\Formatter\Tokens\TagToken;

abstract class AbstractVisitor implements FormatterVisitorInterface
{
    /** @var array|FullTagToken[] */
    protected $tagStack = [];

    public function visitFullText(FullTextToken $fullTextToken)
    {
        $this->tagDepth = 0;
        $this->tagStack = [];
        $iterator = $fullTextToken->getIterator();
        for ($iterator->rewind();$iterator->valid();$iterator->next()) {
            $iterator->current()->accept($this);
        }
    }

    public function visitFullTagToken(FullTagToken $fullTagToken)
    {
        if ($fullTagToken->isStartTag()) {
            array_push($this->tagStack, $fullTagToken);
            $this->afterFullTagStart($fullTagToken);
        }
        if ($fullTagToken->isCloseTag()) {
            $lastOpenTag = end($this->tagStack);
            if ($fullTagToken->getIterator()->count() == 0) {
                foreach ($lastOpenTag->getIterator() as $item) {
                    $fullTagToken->push(clone $item);
                }
            }
            $this->beforeFullTagClose($fullTagToken);
            array_pop($this->tagStack);
        }
    }

    protected function afterFullTagStart(FullTagToken $fullTagToken)
    {
        /** @var TagToken $item */
        foreach ($fullTagToken->getIterator() as $item) {
            $item->accept($this);
        }
    }

    protected function beforeFullTagClose(FullTagToken $fullTagToken)
    {
        /** @var TagToken $item */
        foreach ($fullTagToken->getIterator() as $item) {
            $item->accept($this);
        }
    }
}
