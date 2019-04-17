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

use Symfony\Component\Console\Formatter\Token\DecorationToken;
use Symfony\Component\Console\Formatter\Token\EosToken;
use Symfony\Component\Console\Formatter\Token\FullTagToken;
use Symfony\Component\Console\Formatter\Token\FullTextToken;
use Symfony\Component\Console\Formatter\Token\SeparatorToken;
use Symfony\Component\Console\Formatter\Token\TagToken;
use Symfony\Component\Console\Formatter\Token\Token;
use Symfony\Component\Console\Formatter\Token\TokenInterface;
use Symfony\Component\Console\Formatter\Token\WordToken;

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

    public function visitFullText(FullTextToken $fullTextToken): void
    {
        // reset
        $this->cursor = 0;
        parent::visitFullText($fullTextToken);
    }

    public function visitSeparator(SeparatorToken $separatorToken): void
    {
        switch ($separatorToken->getOriginalStringRepresentation()) {
            case "\n":
                // Insert a new line character
                $this->fillUp($separatorToken);
                $this->newLineReset();
                break;
            default:
                $this->cursor += $separatorToken->getLength();
        }
    }

    public function visitWord(WordToken $wordToken): void
    {
        // If the current line + the new token is longer than the limit.
        if ($this->activeStyle->getWidth() && $this->cursor + $wordToken->getLength() > $this->activeStyle->getWidth()) {
            // If the word is needed to cut...
            if ($this->wordNeedToCut($wordToken)) {
                $word = $wordToken->getOriginalStringRepresentation();
                $wordCursor = 0;
                // Cut the word
                while ($wordCursor < $wordToken->getLength()) {
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
                        $this->addNewLine($wordToken);
                    }
                    $length = min($this->activeStyle->getWidth() - $this->cursor, $wordToken->getLength() - $wordCursor);
                    $block = \mb_substr($word, $wordCursor, $length);
                    // Insert new token
                    $wordToken->insertBefore(new WordToken($block));
                    $wordCursor += $length;
                    $this->cursor += $length;
                }
                // Remove the original token
                $wordToken->remove();
            } else {
                // If the token doe
                $this->addNewLine($wordToken);
                $this->cursor += $wordToken->getLength();
            }
        } else {
            $this->cursor += $wordToken->getLength();
        }
    }

    /**
     * It decides that the word needs to cut (eg. longer than 1 line).
     *
     * @param WordToken $token
     *
     * @return bool
     */
    protected function wordNeedToCut(WordToken $token): bool
    {
        if ($this->tokenIsAnUrl($token) && !$this->activeStyle->isCutUrls()) {
            return false;
        }

        $cutLength = $this->activeStyle->getWordCutLimit();

        return $cutLength && $token->getLength() > $cutLength;
    }

    /**
     * Check the token is an URL.
     *
     * @param WordToken $token
     *
     * @return bool
     */
    protected function tokenIsAnUrl(WordToken $token)
    {
        return 0 === strpos($token->getOriginalStringRepresentation(), 'http://')
            || 0 === strpos($token->getOriginalStringRepresentation(), 'https://');
    }

    public function visitFullTagToken(FullTagToken $fullTagToken): void
    {
        parent::visitFullTagToken($fullTagToken);
        // If it is a simple close tag: </tag>, we close and delete the superfluous global configuration from the stack.
        if ($fullTagToken->isCloseTag() && !$fullTagToken->isSelfClosed()) {
            $depth = \count($this->tagStack);
            if (\array_key_exists($depth + 1, $this->globalStyleStack)) {
                unset($this->globalStyleStack[$depth - 1]);
            }
            $this->resetActiveStyle();
        }
    }

    public function visitTag(TagToken $tagToken): void
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

    public function visitEos(EosToken $eosToken): void
    {
        $this->fillUp($eosToken);
    }

    /** @codeCoverageIgnore */
    public function visitDecoration(DecorationToken $decorationToken): void
    {
        // do nothing
    }

    /**
     * Fill up, start a new line and reset.
     *
     * @param TokenInterface $token
     */
    protected function addNewLine(TokenInterface $token): void
    {
        $originalToken = $token;
        // We search the last "token" of the current line.
        while (!$token->isFirst()) {
            $prev = $token->prevSibling();
            if ($prev->keepTogetherWithNextSibling() || $token->keepTogetherWithPreviousSibling()) {
                $token = $prev;
            } elseif ($prev instanceof SeparatorToken && $prev->isEmpty()) {
                $prev->remove();
                $this->cursor -= $prev->getLength();
                break;
            } else {
                break;
            }
        }
        // We try to avoid the:
        //      - start full text width a "\n"
        //      - double "\n"
        if (!$token->isFirst() && !$this->tokenIsANewLineString($prev)) {
            $this->fillUp($token);
            $token->insertBefore(new SeparatorToken("\n"));
        }
        // reset
        $this->newLineReset($originalToken->prevSibling());
    }

    /**
     * Reset the cursor position at the concrete token. It goes back until a new line separator token or the first token.
     *
     * @param TokenInterface|null $token
     */
    protected function newLineReset(TokenInterface $token = null): void
    {
        $this->cursor = 0;
        if (null !== $token) {
            while ($token instanceof Token && !$token->isFirst() && !$this->tokenIsANewLineString($token)) {
                $this->cursor += $token->getLength();
                $token = $token->prevSibling();
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
        return $token instanceof SeparatorToken && "\n" == $token->getOriginalStringRepresentation();
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
     * @param TokenInterface $newLineBorderToken
     */
    protected function fillUp(TokenInterface $newLineBorderToken): void
    {
        if ($this->activeStyle->getFillUpString()) {
            $missingChars = $this->activeStyle->getWidth() - $this->cursor;
            if ($missingChars > 0 && $missingChars <= $this->activeStyle->getWidth()) {
                $patternLength = \mb_strlen($this->activeStyle->getFillUpString());
                $fillUpChars = \mb_substr(str_repeat(
                    $this->activeStyle->getFillUpString(),
                    ceil($missingChars / $patternLength)
                ), -$missingChars);
                $newLineBorderToken->insertBefore(new DecorationToken($fillUpChars));
            }
        }
    }
}
