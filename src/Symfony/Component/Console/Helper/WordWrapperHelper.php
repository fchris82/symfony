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
 * It helps you to wrap long text with pretty breaks and useful cuts. You can control the cuts with the control option:
 *      - CUT_DISABLE:          Always break the text at word boundary.
 *      - CUT_LONG_WORDS:       If the word is longer than one row it will be cut.
 *      - CUT_WORDS:            Always break at set length, it will cut all words. It would be useful if you have little
 *                              space. (Info: It "contains" the CUT_LONG_WORDS option)
 *      - CUT_URLS:             Lots of terminal can recognize URL-s in text and make them clickable (if there isn't break
 *                              inside the URL) The URLS can be long, default we keep it in one block even if it gets ugly
 *                              response. You can switch this behavior off with this option. The result will be pretty,
 *                              but the URL won't be clickable.
 *      - CUT_FILL_UP_MISSING:  The program will fill up the rows with spaces in order to every row will be same long.
 *      - CUT_NO_REPLACE_EOL:   The program will replace the PHP_EOL in the input string to $break.
 *
 * <code>
 *      $message = "<comment>This is a comment message with <info>info</info></comment> ...";
 *      // Default:
 *      $output->writeln(WordWrapperHelper::wrap($message, 120);
 *      // Use custom settings:
 *      $output->writeln(WordWrapperHelper::wrap(
 *          $message,
 *          20,
 *          WordWrapperHelper::CUT_ALL | WordWrapperHelper::CUT_FILL_UP_MISSING,
 *          PHP_EOL
 *      );
 * </code>
 *
 * Known problems, limitations:
 *      - You can't call WordWrapperHelper::wrap() inside a "running wrap" because there are "cache" properties and
 *          it causes problems within a Singleton class. Solution: you can create a WordWrapperHelper object, and
 *          use the $wrapper->wordwrap() non-static method.
 *      - If you use escaped tags AND (the line width is too short OR you use the CUT_WORDS option): `\<error>Message\</error>`,
 *          the wrapper could wrap inside the tag:
 *
 *              \<error>Me
 *              ssage\</er
 *              ror>
 *
 *          In this case maybe the OutputFormatter won't remove the second `\` character, because the wrapper broke the
 *          tag also, so it will shown like this:
 *
 *              <error>Me
 *              ssage\</er
 *              ror>
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
     * @param string                         $string The text
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
     * @param string                         $string The text
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
