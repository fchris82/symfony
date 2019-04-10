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
use Symfony\Contracts\Service\ResetInterface;

/**
 * @author Jean-François Simon <contact@jfsimon.fr>
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class OutputFormatterStyleStack implements ResetInterface
{
    /**
     * @var OutputFormatterStyleInterface[]
     */
    private $styles;

    private $emptyStyle;

    public function __construct(OutputFormatterStyleInterface $emptyStyle = null)
    {
        $this->emptyStyle = $emptyStyle ?: new OutputFormatterStyle();
        $this->reset();
    }

    /**
     * Resets stack (ie. empty internal arrays).
     */
    public function reset()
    {
        $this->styles = [];
    }

    /**
     * Pushes a style in the stack.
     *
     * @param int                           $depth
     * @param OutputFormatterStyleInterface $style
     */
    public function push(int $depth, OutputFormatterStyleInterface $style)
    {
        $this->styles[$depth] = $style;
    }

    /**
     * Pops a style from the stack.
     *
     * @param int                                $depth
     *
     * @return OutputFormatterStyleInterface|null
     */
    public function pop(int $depth = null)
    {
        if (empty($this->styles)) {
            return $this->emptyStyle;
        }

        if (null === $depth) {
            $stackedStyle = array_pop($this->styles);
            return $stackedStyle;
        } elseif (array_key_exists($depth, $this->styles)) {
            $stackedStyle = $this->styles[$depth];
            $length = array_search($depth, array_keys($this->styles));
            $this->styles = \array_slice($this->styles, 0, $length);
            return $stackedStyle;
        }

        return null;
    }

    public function popByStyle(OutputFormatterStyleInterface $style)
    {
        /**
         * @var int $index
         * @var OutputFormatterStyleInterface $stackedStyle
         */
        foreach (array_reverse($this->styles) as $index => $stackedStyle) {
            if ($style->start().$style->close() === $stackedStyle->start().$stackedStyle->close()) {
                $this->styles = \array_slice($this->styles, 0, \count($this->styles) - $index - 1, true);

                return $stackedStyle;
            }
        }

        throw new InvalidArgumentException('Incorrectly nested style tag found.');
    }

    public function count()
    {
        return \count($this->styles);
    }

    /**
     * Computes current style with stacks top codes.
     *
     * @return OutputFormatterStyle
     */
    public function getCurrent()
    {
        if (empty($this->styles)) {
            return $this->emptyStyle;
        }

        return end($this->styles);
    }

    /**
     * @return $this
     */
    public function setEmptyStyle(OutputFormatterStyleInterface $emptyStyle)
    {
        $this->emptyStyle = $emptyStyle;

        return $this;
    }

    /**
     * @return OutputFormatterStyleInterface
     */
    public function getEmptyStyle()
    {
        return $this->emptyStyle;
    }
}
