<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter\Tokens;

use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;

/**
 * Full tag:
 *
 *      <tag1=option1,option2:value2;tag2=option3>
 *       ^^^^^^^^^^^^^^^^^^^^^^^^^^^ ^^^^^^^^^^^^
 *                    Tag 1             Tag 2
 *
 * or the close tag:
 *
 *      </tag1> or </>
 *
 * It contains TagTokens.
 *
 * @see TagToken
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class FullTagToken extends TokenWithChildren
{
    /** @var bool */
    protected $isStart = true;
    /** @var bool */
    protected $isClose = false;

    public function __construct(string $stringRepresentation)
    {
        parent::__construct($stringRepresentation);
        // Read the content without <> characters.
        $tagContent = \mb_substr($stringRepresentation, 1, -1);
        if ('/' == $tagContent[0]) {
            // if the first character a /, it is a close tag.
            $this->isStart = false;
            $this->isClose = true;
            $tagContent = \mb_substr($tagContent, 1);
        } elseif ('/' == \mb_substr($tagContent, -1)) {
            // if the last character a /, it is a self closed tag.
            $this->isClose = true;
            $tagContent = \mb_substr($tagContent, 0, -1);
        }

        // parse the "tags" in full tag
        $tags = explode(';', $tagContent);
        foreach ($tags as $tag) {
            $tag = ltrim($tag);
            if ($tag) {
                $tagToken = TagToken::parse($tag);
                $this->push($tagToken);
            }
        }
    }

    public function accept(FormatterVisitorInterface $formatterVisitor): void
    {
        $formatterVisitor->visitFullTagToken($this);
    }

    /**
     * Tags are hidden texts, so the length of every token is 0.
     *
     * @return int
     */
    public function getLength(): int
    {
        return 0;
    }

    /**
     * It gets true if the tag is a start tag:
     *  - Base: <tag1>
     *  - Self closed: <tag2/>
     *
     * @return bool
     */
    public function isStartTag(): bool
    {
        return $this->isStart;
    }

    /**
     * It gets true if the tag is a close tag:
     *  - Base: </tag1>
     *  - Short: </>
     *  - Self closed: <tag2/>
     *
     * @return bool
     */
    public function isCloseTag(): bool
    {
        return $this->isClose;
    }

    /**
     * It gets true if the tag is self closed:
     *  - Self closed: <tag1/>
     *
     * @return bool
     */
    public function isSelfClosed(): bool
    {
        return $this->isStartTag() && $this->isCloseTag();
    }

    public function keepTogetherWithNextSibling(): bool
    {
        return $this->isStartTag();
    }

    public function keepTogetherWithPreviousSibling(): bool
    {
        return $this->isCloseTag() && !$this->isSelfClosed();
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
