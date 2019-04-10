<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\Visitors\DecoratorVisitorInterface;
use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;
use Symfony\Component\Console\Formatter\Visitors\HrefVisitor;
use Symfony\Component\Console\Formatter\Visitors\OutputBuildVisitorInterface;
use Symfony\Component\Console\Formatter\Visitors\PrintVisitor;
use Symfony\Component\Console\Formatter\Visitors\StyleVisitor;
use Symfony\Component\Console\Formatter\Visitors\VisitorIterator;
use Symfony\Component\Console\Formatter\Visitors\WrapperVisitor;

/**
 * Formatter class for console output.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @author Roland Franssen <franssen.roland@gmail.com>
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class OutputFormatter implements OutputFormatterInterface
{
    /** @var bool */
    protected $decorated;
    /** @var Lexer */
    protected $lexer;
    /** @var VisitorIterator|FormatterVisitorInterface[] */
    protected $visitorIterator;

    /**
     * Escapes "<" special char in given text.
     *
     * @param string $text Text to escape
     *
     * @return string Escaped text
     */
    public static function escape($text)
    {
        $text = preg_replace('/([^\\\\]?)</', '$1\\<', $text);

        return self::escapeTrailingBackslash($text);
    }

    /**
     * Escapes trailing "\" in given text.
     *
     * @param string $text Text to escape
     *
     * @return string Escaped text
     *
     * @internal
     */
    public static function escapeTrailingBackslash($text)
    {
        if ('\\' === substr($text, -1)) {
            $len = \strlen($text);
            $text = rtrim($text, '\\');
            $text = str_replace("\0", '', $text);
            $text .= str_repeat("\0", $len - \strlen($text));
        }

        return $text;
    }

    /**
     * Initializes console output formatter.
     *
     * @param bool                            $decorated Whether this formatter should actually decorate strings
     * @param OutputFormatterStyleInterface[] $styles    Array of "name => FormatterStyle" instances
     */
    public function __construct(bool $decorated = false, array $styles = [])
    {
        $this->decorated = $decorated;
        $this->lexer = new Lexer();
        $this->visitorIterator = new VisitorIterator();
        $this->initVisitors($styles);
    }

    public function addVisitor(FormatterVisitorInterface $visitor, int $priority = 0): self
    {
        $this->visitorIterator->insert($visitor, $priority);

        return $this;
    }

    protected function initVisitors(array $styles)
    {
        if ($this->visitorIterator->count() === 0) {
            $this->addVisitor(new WrapperVisitor(), 999);
            $this->addVisitor(new StyleVisitor($styles));
            $this->addVisitor(new HrefVisitor());
            $this->addVisitor(new PrintVisitor(), -999);
        }
    }

    public function getVisitorByClass($class)
    {
        foreach ($this->visitorIterator as $visitor) {
            if ($visitor instanceof $class) {
                return $visitor;
            }
        }

        throw new \InvalidArgumentException(sprintf('Missing visitor class: `%s`', $class));
    }

    /**
     * {@inheritdoc}
     */
    public function setDecorated($decorated)
    {
        $this->decorated = (bool) $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function isDecorated()
    {
        return $this->decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function setStyle($name, OutputFormatterStyleInterface $style)
    {
        /** @var StyleVisitor $styleVisitor */
        $styleVisitor = $this->getVisitorByClass(StyleVisitor::class);
        $styleVisitor->setStyle($name, $style);
    }

    /**
     * {@inheritdoc}
     */
    public function hasStyle($name)
    {
        /** @var StyleVisitor $styleVisitor */
        $styleVisitor = $this->getVisitorByClass(StyleVisitor::class);
        return $styleVisitor->hasStyle($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getStyle($name)
    {
        /** @var StyleVisitor $styleVisitor */
        $styleVisitor = $this->getVisitorByClass(StyleVisitor::class);
        return $styleVisitor->getStyle($name);
    }

    /**
     * {@inheritdoc}
     */
    public function format($message)
    {
        $fullTextTokens = $this->lexer->tokenize($message);
        /** @var FormatterVisitorInterface $visitor */
        foreach ($this->visitorIterator as $visitor) {
            // skips the decorator visitors
            if (!$this->isDecorated() && $visitor instanceof DecoratorVisitorInterface) {
                continue;
            }
            $fullTextTokens->accept($visitor);
        }
        if ($visitor instanceof OutputBuildVisitorInterface) {
            $output = $visitor->getOutput();
            if (false !== strpos($output, "\0")) {
                return strtr($output, "\0", '\\');
            }

            return $output;
        }

        throw new InvalidArgumentException(sprintf('The last visitor should be implemented the `%s` interface!', OutputBuildVisitorInterface::class));
    }
}
