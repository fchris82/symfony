<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter\Visitors;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyleStack;
use Symfony\Component\Console\Formatter\Tokens\DecorationToken;
use Symfony\Component\Console\Formatter\Tokens\EosToken;
use Symfony\Component\Console\Formatter\Tokens\FullTagToken;
use Symfony\Component\Console\Formatter\Tokens\FullTextToken;
use Symfony\Component\Console\Formatter\Tokens\SeparatorToken;
use Symfony\Component\Console\Formatter\Tokens\TagToken;
use Symfony\Component\Console\Formatter\Tokens\WordToken;

/**
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class StyleVisitor extends AbstractVisitor implements DecoratorVisitorInterface
{
    /** @var array|OutputFormatterStyle[] */
    protected $styles = [];
    protected $styleStack;
    /** @var OutputFormatterStyleInterface */
    protected $currentStyle;

    /**
     * You can register custom styles next to base styles. You can override too the originals.
     *
     * @param array|OutputFormatterStyle[] $styles
     */
    public function __construct(array $styles = [])
    {
        $this->setStyle('error', new OutputFormatterStyle('white', 'red'));
        $this->setStyle('info', new OutputFormatterStyle('green'));
        $this->setStyle('comment', new OutputFormatterStyle('yellow'));
        $this->setStyle('question', new OutputFormatterStyle('black', 'cyan'));

        foreach ($styles as $name => $style) {
            $this->setStyle($name, $style);
        }

        $this->styleStack = new OutputFormatterStyleStack();
    }

    /**
     * Add a new style.
     *
     * @param string                        $name  The tag name: <{$name}>
     * @param OutputFormatterStyleInterface $style The style
     */
    public function setStyle(string $name, OutputFormatterStyleInterface $style)
    {
        $this->styles[strtolower($name)] = $style;
    }

    /**
     * Check a style by name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasStyle(string $name): bool
    {
        return isset($this->styles[strtolower($name)]);
    }

    /**
     * Get a style by name.
     *
     * @param string $name
     *
     * @return OutputFormatterStyle
     */
    public function getStyle(string $name): OutputFormatterStyle
    {
        if (!$this->hasStyle($name)) {
            throw new InvalidArgumentException(sprintf('Undefined style: %s', $name));
        }

        return $this->styles[strtolower($name)];
    }

    public function visitFullText(FullTextToken $fullTextToken): void
    {
        parent::visitFullText($fullTextToken);
    }

    public function visitSeparator(SeparatorToken $separatorToken): void
    {
        // We close every line and start a new line
        if ($this->styleStack->count() && "\n" == $separatorToken->getOriginalStringRepresentation()) {
            $currentStyle = $this->styleStack->getCurrent();
            $separatorToken->insertBefore(new DecorationToken($currentStyle->close()));
            $separatorToken->insertAfter(new DecorationToken($currentStyle->start()));
        }
    }

    public function visitWord(WordToken $wordToken): void
    {
        // do nothing
    }

    public function visitFullTagToken(FullTagToken $fullTagToken): void
    {
        if ($fullTagToken->isStartTag()) {
            array_push($this->tagStack, $fullTagToken);
        }

        /** @var TagToken $tagToken */
        foreach ($fullTagToken->getIterator() as $tagToken) {
            $tagToken->accept($this);
        }

        // If something was built in `visitTag()` method...
        if (null !== $this->currentStyle) {
            // If something was started, we close it before "open" the new style.
            if ($this->styleStack->count() > 0) {
                $prev = $this->styleStack->getCurrent();
                $fullTagToken->insertBefore(new DecorationToken($prev->close()));
            }
            $this->styleStack->push(\count($this->tagStack), $this->currentStyle);
            $fullTagToken->insertAfter(new DecorationToken($this->currentStyle->start()));
            // reset
            $this->currentStyle = null;
        }
        if ($fullTagToken->isCloseTag()) {
            $currentStyle = $this->styleStack->pop(\count($this->tagStack));
            if ($currentStyle) {
                $fullTagToken->insertBefore(new DecorationToken($currentStyle->close()));
            }
            if ($this->styleStack->count() > 0) {
                $prev = $this->styleStack->getCurrent();
                $fullTagToken->insertAfter(new DecorationToken($prev->start()));
            }
        }

        if ($fullTagToken->isCloseTag()) {
            array_pop($this->tagStack);
        }
    }

    public function visitTag(TagToken $tagToken): void
    {
        if ($tagToken->getParent()->isStartTag()) {
            switch ($tagToken->getName()) {
                case 'fg':
                    $this->getCurrentStyle()->setForeground($tagToken->getValue());
                    break;
                case 'bg':
                    $this->getCurrentStyle()->setBackground($tagToken->getValue());
                    break;
                case 'options':
                    $this->getCurrentStyle()->setOptions($tagToken->getValues());
                    break;
                default:
                    if (array_key_exists($tagToken->getName(), $this->styles)) {
                        $this->currentStyle = $this->styles[$tagToken->getName()];
                    }
                    break;
            }
        } else {
            if (array_key_exists($tagToken->getName(), $this->styles)) {
                $style = $this->styles[$tagToken->getName()];
                $this->styleStack->popByStyle($style);
                $tagToken->getParent()->insertBefore(new DecorationToken($style->close()));
            }
        }
    }

    protected function getCurrentStyle()
    {
        if (!$this->currentStyle) {
            $this->currentStyle = new OutputFormatterStyle();
        }

        return $this->currentStyle;
    }

    public function visitEos(EosToken $eosToken): void
    {
        // It closes every opened style
        while ($this->styleStack->count()) {
            $style = $this->styleStack->pop();
            $eosToken->insertBefore(new DecorationToken($style->close()));
        }
    }

    public function visitDecoration(DecorationToken $decorationToken): void
    {
        // do nothing
    }
}
