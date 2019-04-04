<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.01.
 * Time: 13:36
 */

namespace Symfony\Component\Console\Formatter\Tokens;


use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;

class FullTagToken extends TokenWithChildren
{
    protected $isStart = true;
    protected $isClose = false;

    public function __construct(string $stringRepresentation)
    {
        parent::__construct($stringRepresentation);
        $tagContent = \mb_substr($stringRepresentation, 1, -1);
        if ('/' == $tagContent[0]) {
            $this->isStart = false;
            $this->isClose = true;
            $tagContent = \mb_substr($tagContent, 1);
        } elseif ('/' == \mb_substr($tagContent, -1)) {
            $this->isClose = true;
            $tagContent = \mb_substr($tagContent, 0, -1);
        }

        $tags = explode(';', $tagContent);
        foreach ($tags as $tag) {
            $tag = ltrim($tag);
            if ($tag) {
                $tagToken = TagToken::parse($tag);
                $this->push($tagToken);
            }
        }
    }

    public function accept(FormatterVisitorInterface $formatterVisitor)
    {
        $formatterVisitor->visitFullTagToken($this);
    }

    public function getLength(): int
    {
        return 0;
    }

    public function isStartTag(): bool
    {
        return $this->isStart;
    }

    public function isCloseTag(): bool
    {
        return $this->isClose;
    }

    public function isSelfClosed(): bool
    {
        return $this->isStartTag() && $this->isCloseTag();
    }

    public function widthNextSibling(): bool
    {
        return $this->isStartTag();
    }

    public function widthPreviousSibling(): bool
    {
        return $this->isCloseTag();
    }

    public function __toString(): string
    {
        $children = [];
        foreach ($this->children as $child) {
            $children[] = (string) $child;
        }
        return sprintf('%s(%s)', $this->typeToString(), implode('+', $children));
    }
}
