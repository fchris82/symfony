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
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyleStack;
use Symfony\Component\Console\Formatter\Token\DecorationToken;
use Symfony\Component\Console\Formatter\Token\EosToken;
use Symfony\Component\Console\Formatter\Token\FullTagToken;
use Symfony\Component\Console\Formatter\Token\FullTextToken;
use Symfony\Component\Console\Formatter\Token\SeparatorToken;
use Symfony\Component\Console\Formatter\Token\TagToken;
use Symfony\Component\Console\Formatter\Token\WordToken;

/**
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class StyleVisitor extends AbstractVisitor implements DecoratorVisitorInterface
{
    /** @var array|OutputFormatterStyle[] */
    protected $styles = [];
    /** @var array|bool[] */
    protected $isStyleTagStack = [];
    /** @var OutputFormatterStyleStack */
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

    protected function getCurrentStyle()
    {
        if (!$this->currentStyle) {
            $this->currentStyle = new OutputFormatterStyle();
        }

        return $this->currentStyle;
    }

    protected function handleFullTagToken(FullTagToken $fullTagToken): void
    {
        if ($fullTagToken->isStartTag()) {
            array_push($this->tagStack, $fullTagToken);
        }

        $this->handleTags($fullTagToken);

        // If something was built in `visitTag()` method...
        if (null !== $this->currentStyle) {
            array_push($this->isStyleTagStack, true);
            // If something was started, we close it before "open" the new style.
            if ($this->styleStack->count() > 0) {
                $prev = $this->styleStack->getCurrent();
                $this->insertItem($this->i, [Lexer::TYPE_DECORATION, $prev->close()]);
            }
            $this->styleStack->push($this->currentStyle);
            $this->insertItem($this->i+1, [Lexer::TYPE_DECORATION, $this->currentStyle->start()]);
            // reset
            $this->currentStyle = null;
        } elseif ($fullTagToken->isStartTag()) {
            array_push($this->isStyleTagStack, false);
        }
        if ($fullTagToken->isCloseTag()) {
            $isStyleTag = array_pop($this->isStyleTagStack);
            if ($isStyleTag) {
                $currentStyle = $this->styleStack->pop();
                $this->insertItem($this->i, [Lexer::TYPE_DECORATION, $currentStyle->close()]);
                if ($this->styleStack->count() > 0) {
                    $prev = $this->styleStack->getCurrent();
                    $this->insertItem($this->i+1, [Lexer::TYPE_DECORATION, $prev->start()]);
                }
            }
        }

        if ($fullTagToken->isCloseTag()) {
            array_pop($this->tagStack);
        }
    }

    protected function handleWord(string $value)
    {
        // do nothing
    }

    protected function handleSeparator(string $value)
    {
        // We close every line and start a new line
        if ($this->styleStack->count() && "\n" == $value) {
            $currentStyle = $this->styleStack->getCurrent();
            $this->insertItem($this->i, [Lexer::TYPE_DECORATION, $currentStyle->close()]);
            $this->insertItem($this->i+1, [Lexer::TYPE_DECORATION, $currentStyle->start()]);
        }
    }

    protected function handleTag(TagToken $tagToken)
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
                    if (\array_key_exists($tagToken->getName(), $this->styles)) {
                        $this->currentStyle = $this->styles[$tagToken->getName()];
                    }
                    break;
            }
        } else {
            // Reposition because of unclosed tags: <info>...<comment>...</info> (Missing </comment>)
            if (\array_key_exists($tagToken->getName(), $this->styles)) {
                $style = $this->styles[$tagToken->getName()];
                $currentStyle = $this->styleStack->getCurrent();
                while ($style->start().$style->close() != $currentStyle->start().$currentStyle->close()) {
                    if (array_pop($this->isStyleTagStack)) {
                        $this->styleStack->pop();
                    }
                    $currentStyle = $this->styleStack->getCurrent();
                }
                while (!$this->isStyleTagStack[\count($this->isStyleTagStack)-1]) {
                    array_pop($this->isStyleTagStack);
                }
            }
        }
    }

    protected function handleEos(EosToken $eosToken)
    {
        // It closes every opened style
        while ($this->styleStack->count()) {
            $style = $this->styleStack->pop();
            $this->insertItem($this->i, [Lexer::TYPE_DECORATION, $style->close()]);
        }
    }

    protected function handleDecoration(string $value)
    {
        // do nothing
    }
}
