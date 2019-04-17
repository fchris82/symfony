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
use Symfony\Component\Console\Formatter\Visitor\DecoratorVisitorInterface;
use Symfony\Component\Console\Formatter\Visitor\FormatterVisitorInterface;
use Symfony\Component\Console\Formatter\Visitor\HrefVisitor;
use Symfony\Component\Console\Formatter\Visitor\OutputBuildVisitorInterface;
use Symfony\Component\Console\Formatter\Visitor\PrintVisitor;
use Symfony\Component\Console\Formatter\Visitor\StyleVisitor;
use Symfony\Component\Console\Formatter\Visitor\VisitorIterator;
use Symfony\Component\Console\Formatter\Visitor\WrapperVisitor;
use Symfony\Component\Console\Helper\Helper;

/**
 * Formatter class for console output.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @author Roland Franssen <franssen.roland@gmail.com>
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class TokenizeOutputFormatter implements TokenizeOutputFormatterInterface
{
    /** @var bool */
    protected $decorated;
    /** @var Lexer */
    protected $lexer;
    /** @var VisitorIterator|FormatterVisitorInterface[] */
    protected $visitorIterator;

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
        if (0 === $this->visitorIterator->count()) {
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
        $fullTextTokens = $this->lexer->tokenize((string) $message);
        /** @var FormatterVisitorInterface $visitor */
        foreach ($this->visitorIterator as $visitor) {
            // Skips the decorator visitors
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

    /**
     * {@inheritdoc}
     */
    public function removeDecoration($str)
    {
        // Escape escape
        $str = str_replace('\\<', "\0", $str);
        $str = preg_replace(sprintf(
            "{(<(%s)>|</(%s)?>|\033[^m]+m)}",
            Helper::FORMAT_TAG_REGEX,
            Helper::FORMAT_TAG_REGEX
        ), '', $str);
        // Unescape escaped < character
        $str = str_replace("\0", '<', $str);

        return $str;
    }
}
