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

use Symfony\Component\Console\Formatter\Lexer;
use Symfony\Component\Console\Formatter\Token\DecorationToken;
use Symfony\Component\Console\Formatter\Token\EosToken;
use Symfony\Component\Console\Formatter\Token\FullTagToken;
use Symfony\Component\Console\Formatter\Token\FullTextToken;
use Symfony\Component\Console\Formatter\Token\SeparatorToken;
use Symfony\Component\Console\Formatter\Token\TagToken;
use Symfony\Component\Console\Formatter\Token\Token;
use Symfony\Component\Console\Formatter\Token\TokenInterface;
use Symfony\Component\Console\Formatter\Token\WordToken;
use Symfony\Component\Console\Helper\Helper;

/**
 * Wrapping the text. Eg:.
 *
 *      <wrap=50,cut_words:30,cut_urls,fill_up:. />Lorem ipsum dolor sit amet
 *                                             ^^
 *                                             This is 2 chars!
 * Output:
 *
 *      Lorem ipsum dolor sit amet . . . . . . . . . . . .
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class WrapperVisitor extends AbstractVisitor
{
    /**
     * Cursor position in the current line.
     *
     * @var int
     */
    protected $cursor = 0;

    /** @var WrapperStyle */
    protected $baseStyle;
    /** @var array|WrapperStyle[] */
    protected $localStyleStack = [];
    /** @var array|WrapperStyle[] */
    protected $globalStyleStack = [];
    /** @var WrapperStyle */
    protected $activeStyle;

    /**
     * WrapperVisitor constructor.
     *
     * @param WrapperStyle $baseStyle
     */
    public function __construct(WrapperStyle $baseStyle = null)
    {
        $this->setBaseStyle($baseStyle ?: new WrapperStyle());
    }

    public function setBaseStyle(WrapperStyle $baseStyle): void
    {
        $this->baseStyle = $baseStyle;
        $this->setActiveStyle();
    }

    /**
     * It decides that the word needs to cut (eg. longer than 1 line).
     *
     * @param string $word
     *
     * @return bool
     */
    protected function wordNeedToCut(string $word): bool
    {
        if ($this->wordIsAnUrl($word) && !$this->activeStyle->isCutUrls()) {
            return false;
        }

        $cutLength = $this->activeStyle->getWordCutLimit();

        return $cutLength && Helper::strlen($word) > $cutLength;
    }

    /**
     * Check the token is an URL.
     *
     * @param string $word
     *
     * @return bool
     */
    protected function wordIsAnUrl(string $word)
    {
        return 0 === strpos($word, 'http://')
            || 0 === strpos($word, 'https://');
    }

    /**
     * Fill up, start a new line and reset.
     */
    protected function addNewLine(): void
    {
        $tokenCursor = $this->i;
        $token = $this->tokens[$tokenCursor];
        // We search the last "token" of the current line.
        while ($tokenCursor > 0) {
            $prev = $this->tokens[--$tokenCursor];
            if ($this->checkKeepTogetherWithNextSibling($prev) || $this->checkKeepTogetherWithPreviousSibling($token)) {
                $token = $prev;
            } elseif (' ' == $prev) {
                $this->removeItem($tokenCursor);
                $this->cursor--;
                break;
            } else {
                $tokenCursor++;
                break;
            }
        }
        // We try to avoid the:
        //      - start full text width a "\n"
        //      - double "\n"
        if ($tokenCursor > 0 && $prev[1] !== "\n") {
            $this->fillUp($tokenCursor);
            $this->insertItem($tokenCursor, [Lexer::TYPE_SEPARATOR, "\n"]);
        }
        // reset
        $this->newLineReset($this->i-1);
    }

    protected function checkKeepTogetherWithNextSibling($token)
    {
        if ($token instanceof Token) {
            return $token->keepTogetherWithNextSibling();
        }

        return false;
    }

    protected function checkKeepTogetherWithPreviousSibling($token)
    {
        if ($token instanceof Token) {
            return $token->keepTogetherWithPreviousSibling();
        }

        return false;
    }

    /**
     * Reset the cursor position at the concrete token. It goes back until a new line separator token or the first token.
     *
     * @param int|null $position
     */
    protected function newLineReset(int $position = null): void
    {
        $this->cursor = 0;
        if (null !== $position) {
            while ($position>0 && $this->tokens[$position] !== "\n") {
                /** @var string|Token $token */
                $token = $this->tokens[$position];
                $this->cursor += $token instanceof Token ? $token->getLength() : Helper::strlen($token[1]);
                $position--;
            }
        }
    }

    /**
     * Detect the new line tokens.
     *
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function tokenIsANewLineString(TokenInterface $token): bool
    {
        return "\n" === $token;
    }

    /**
     * There are 2 different configuration stacks:
     *      - local means it has begin and end: "<wrap=120>...</wrap>"
     *      - global means it doesn't have end: "<wrap=120/>"
     * You can combine them:.
     *
     *      <wrap=120/>.....<wrap=80>....</wrap>...
     *                  ^^^           ^^        ^^^
     *                  120           80        120
     *
     * This function sets the "current" configurations.
     */
    protected function resetActiveStyle(): void
    {
        $localDepth = $this->findLastConfigurationDepth($this->localStyleStack);
        // set -1 if it is null
        if (null === $localDepth) {
            $localDepth = -1;
        }
        $globalDepth = $this->findLastConfigurationDepth($this->globalStyleStack);
        // set -1 if it is null
        if (null === $globalDepth) {
            $globalDepth = -1;
        }
        if ($globalDepth >= 0 && $globalDepth > $localDepth) {
            $this->setActiveStyle($this->globalStyleStack[$globalDepth]);
        } elseif ($localDepth >= 0 && $localDepth >= $globalDepth) {
            $this->setActiveStyle($this->localStyleStack[$localDepth]);
        } else {
            $this->setActiveStyle(null);
        }
    }

    /**
     * We search the last valid configuration by depth.
     *
     * @param array $configurationStack
     *
     * @return int|null
     */
    protected function findLastConfigurationDepth(array $configurationStack): ?int
    {
        $currentDepth = \count($this->tagStack);
        $last = null;
        foreach ($configurationStack as $depth => $configuration) {
            if ($depth > $currentDepth) {
                return $last;
            }
            $last = $depth;
        }

        return $last;
    }

    /**
     * Set active configuration what the program currently have to use.
     *
     * @param TagToken|null $wrapToken
     *
     * @return WrapperStyle
     */
    protected function parseStyle(TagToken $wrapToken = null): WrapperStyle
    {
        $style = new WrapperStyle();

        if ($wrapToken && 'nowrap' != $wrapToken->getName()) {
            foreach ($wrapToken->getValues() as $value) {
                if (false !== strpos($value, ':')) {
                    list($attrName, $attrValue) = explode(':', $value);
                } elseif (is_numeric($value)) {
                    $attrValue = (int) $value;
                    $attrName = 'width';
                } else {
                    $attrName = $value;
                    $attrValue = null;
                }
                switch ($attrName) {
                    case 'width':
                        $style->setWidth($attrValue);
                        break;
                    case 'cut_words':
                        // If it is set without any value, we cut every words
                        if (null === $attrValue) {
                            $attrValue = 1;
                        }
                        $style->setWordCutLimit($attrValue);
                        break;
                    case 'cut_urls':
                        $style->setCutUrls(true);
                        break;
                    case 'fill_up':
                        // If it is set without any value, we use ' ' (space)
                        if (null === $attrValue) {
                            $attrValue = ' ';
                        }
                        $style->setFillUpString($attrValue);
                        break;
                    default:
                        throw new \InvalidArgumentException(sprintf(
                            'Invalid configuration option: `%s`',
                            $attrName
                        ));
                }
            }
        }

        return $style;
    }

    protected function setActiveStyle(WrapperStyle $style = null)
    {
        $this->activeStyle = $style ?: $this->baseStyle;
    }

    protected function pushStyle(WrapperStyle $style, bool $isGlobal = false): void
    {
        $depth = \count($this->tagStack);
        if ($isGlobal) {
            $this->globalStyleStack[$depth - 1] = $style;
        } else {
            $this->localStyleStack[$depth] = $style;
        }
        $this->setActiveStyle($style);
    }

    /**
     * Insert close characters into the line, before the new line character token.
     *
     * @param int|null $position
     */
    protected function fillUp(int $position = null): void
    {
        if ($this->activeStyle->getFillUpString()) {
            if (null === $position) {
                $position = $this->i;
            }
            $missingChars = $this->activeStyle->getWidth() - $this->cursor;
            if ($missingChars > 0 && $missingChars <= $this->activeStyle->getWidth()) {
                $patternLength = \mb_strlen($this->activeStyle->getFillUpString());
                $fillUpChars = \mb_substr(str_repeat(
                    $this->activeStyle->getFillUpString(),
                    ceil($missingChars / $patternLength)
                ), -$missingChars);
                $this->insertItem($position, [Lexer::TYPE_DECORATION, $fillUpChars]);
            }
        }
    }

    protected function handleWord(string $word)
    {
        $wordLength = Helper::strlen($word);
        // If the current line + the new token is longer than the limit.
        if ($this->activeStyle->getWidth() && $this->cursor + $wordLength > $this->activeStyle->getWidth()) {
            // If the word is needed to cut...
            if ($this->wordNeedToCut($word)) {
                $wordCursor = 0;
                // Cut the word
                while ($wordCursor < $wordLength) {
                    // We try to avoid the ugly cuts where few characters are left at the end of the line, eg:
                    // Ugly:
                    //      lorem ipsum dolor th  <-- 2 characters left
                    //      isisaverylongwordthi
                    //      sisaverylongword
                    // Better:
                    //      lorem ipsum dolor     <-- the word started in the next line
                    //      thisisaverylongwordt
                    //      hisisaverylongword
                    // But we use this "beautifier" function if the word cut limit is longer than 5 characters.
                    $littleBeautifierCorrection = $this->activeStyle->getWordCutLimit() > 5 ? 4 : 0;
                    if ($this->cursor + $littleBeautifierCorrection >= $this->activeStyle->getWidth()) {
                        $this->addNewLine();
                    }
                    $length = min($this->activeStyle->getWidth() - $this->cursor, $wordLength - $wordCursor);
                    $block = \mb_substr($word, $wordCursor, $length);
                    // Insert new token
                    $this->insertItem($this->i, [Lexer::TYPE_WORD, $block]);
                    $wordCursor += $length;
                    $this->cursor += $length;
                }
                // Remove the original token
                $this->removeItem($this->i);
            } else {
                // If the token doe
                $this->addNewLine();
                $this->cursor += $wordLength;
            }
        } else {
            $this->cursor += $wordLength;
        }
    }

    protected function handleSeparator(string $value)
    {
        switch ($value) {
            case "\n":
                // Insert a new line character
                $this->fillUp();
                $this->newLineReset();
                break;
            default:
                $this->cursor += Helper::strlen($value);
        }
    }

    protected function handleFullTagToken(FullTagToken $fullTagToken): void
    {
        parent::handleFullTagToken($fullTagToken);
        // If it is a simple close tag: </tag>, we close and delete the superfluous global configuration from the stack.
        if ($fullTagToken->isCloseTag() && !$fullTagToken->isSelfClosed()) {
            $depth = \count($this->tagStack);
            if (\array_key_exists($depth + 1, $this->globalStyleStack)) {
                unset($this->globalStyleStack[$depth - 1]);
            }
            $this->resetActiveStyle();
        }
    }

    protected function handleTag(TagToken $tagToken)
    {
        if (\in_array($tagToken->getName(), ['wrap', 'nowrap'])) {
            if ($tagToken->getParent()->isStartTag()) {
                $style = $this->parseStyle($tagToken);
                $this->pushStyle($style, $tagToken->getParent()->isSelfClosed());
            } elseif ($tagToken->getParent()->isCloseTag() && !$tagToken->getParent()->isSelfClosed()) {
                $depth = \count($this->tagStack);
                if (\array_key_exists($depth, $this->localStyleStack)) {
                    unset($this->localStyleStack[$depth]);
                    $this->resetActiveStyle();
                }
            }
        }
    }

    protected function handleDecoration(string $value)
    {
        // do nothing
    }

    protected function handleEos(EosToken $eosToken)
    {
        $this->fillUp();
    }
}
