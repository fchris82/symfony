<?php
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Helper;

use Symfony\Component\Console\Formatter\Lexer;
use Symfony\Component\Console\Formatter\LexerInterface;
use Symfony\Component\Console\Formatter\Visitors\OutputBuildVisitorInterface;
use Symfony\Component\Console\Formatter\Visitors\PrintVisitor;
use Symfony\Component\Console\Formatter\Visitors\WrapperStyle;
use Symfony\Component\Console\Formatter\Visitors\WrapperStyleInterface;
use Symfony\Component\Console\Formatter\Visitors\WrapperVisitor;

/**
 * You can wrap the text.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class WordWrapperHelper extends Helper
{
    /**
     * @var self
     */
    protected static $instance;

    /**
     * @var LexerInterface
     */
    protected $lexer;

    /**
     * @var WrapperVisitor
     */
    protected $wrapperVisitor;

    /**
     * @var OutputBuildVisitorInterface
     */
    protected $printVisitor;

    public function __construct()
    {
        $this->lexer = new Lexer();
        $this->wrapperVisitor = new WrapperVisitor();
        $this->printVisitor = new PrintVisitor(PrintVisitor::PRINT_RAW_ESCAPED);
    }

    /**
     * "Singleton.", but it isn't forbidden to create new objects, if you want.
     *
     * @return WordWrapperHelper
     */
    protected static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'WordWrapperHelper';
    }

    /**
     * @param string                         $string       The text
     * @param int|WrapperStyleInterface|null $styleOrWidth
     *
     * @return string
     */
    public static function wrap(string $string, $styleOrWidth = null): string
    {
        $wrapper = self::getInstance();

        return $wrapper->wordwrap($string, $styleOrWidth);
    }

    /**
     * @param string                         $string       The text
     * @param int|WrapperStyleInterface|null $styleOrWidth
     *
     * @return string
     */
    public function wordwrap(string $string, $styleOrWidth = null): string
    {
        $style = $styleOrWidth instanceof WrapperStyleInterface
            ? $styleOrWidth
            : WrapperStyle::create()->setWidth($styleOrWidth);
        $this->wrapperVisitor->setBaseStyle($style);

        $fullText = $this->lexer->tokenize($string);
        $fullText->accept($this->wrapperVisitor);
        $fullText->accept($this->printVisitor);

        return $this->printVisitor->getOutput();
    }
}
