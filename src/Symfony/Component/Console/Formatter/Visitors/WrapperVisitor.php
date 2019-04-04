<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.03.
 * Time: 11:54
 */

namespace Symfony\Component\Console\Formatter\Visitors;

use Symfony\Component\Console\Formatter\Tokens\FullTagToken;
use Symfony\Component\Console\Formatter\Tokens\DecorationToken;
use Symfony\Component\Console\Formatter\Tokens\EosToken;
use Symfony\Component\Console\Formatter\Tokens\FullTextToken;
use Symfony\Component\Console\Formatter\Tokens\SeparatorToken;
use Symfony\Component\Console\Formatter\Tokens\TagToken;
use Symfony\Component\Console\Formatter\Tokens\Token;
use Symfony\Component\Console\Formatter\Tokens\TokenInterface;
use Symfony\Component\Console\Formatter\Tokens\WordToken;

class WrapperVisitor extends AbstractVisitor
{
    protected $cursor = 0;
    /** @var array|TagToken[] */
    protected $localConfigurationStack = [];
    /** @var array|TagToken[] */
    protected $globalConfigurationStack = [];

    /** @var null|int */
    protected $widthLimit;
    /** @var null|int */
    protected $wordCutLimit;
    /** @var bool */
    protected $cutUrls = false;
    /** @var null|string */
    protected $fillUpString;

    public function visitFullText(FullTextToken $fullTextToken)
    {
        $this->cursor = 0;
        parent::visitFullText($fullTextToken);
    }

    public function visitSeparator(SeparatorToken $separatorToken)
    {
        switch ($separatorToken->getOriginalStringRepresentation()) {
            case "\n":
                $this->fillUp($separatorToken);
                $this->newLineReset();
                break;
            default:
                $this->cursor+=$separatorToken->getLength();
        }
    }

    public function visitWord(WordToken $wordToken)
    {
        if ($this->getWidthLimit() && $this->cursor + $wordToken->getLength() > $this->getWidthLimit()) {
            if ($this->wordNeedToCut($wordToken)) {
                $word = $wordToken->getOriginalStringRepresentation();
                $wordCursor = 0;
                while ($wordCursor < $wordToken->getLength()) {
                    $littleBeautifierCorrection = $this->getWordCutLimit() > 5 ? 4 : 0;
                    if ($this->cursor + $littleBeautifierCorrection >= $this->getWidthLimit()) {
                        $this->addNewLine($wordToken);
                    }
                    $length = min($this->getWidthLimit() - $this->cursor, $wordToken->getLength() - $wordCursor);
                    $block = \mb_substr($word, $wordCursor, $length);
                    $wordToken->insertBefore(new WordToken($block));
                    $wordCursor += $length;
                    $this->cursor += $length;
                }
                $wordToken->remove();
            } else {
                $this->addNewLine($wordToken);
                $this->cursor += $wordToken->getLength();
            }
        } else {
            $this->cursor += $wordToken->getLength();
        }
    }

    protected function wordNeedToCut(WordToken $token)
    {
        if ($this->tokenIsAnUrl($token) && !$this->cutUrls) {
            return false;
        }

        $cutLength = $this->getWordCutLimit();
        return $cutLength && $token->getLength() > $cutLength;
    }

    protected function tokenIsAnUrl(WordToken $token)
    {
        return 0 === strpos($token->getOriginalStringRepresentation(), 'http://') || 0 === strpos($token->getOriginalStringRepresentation(), 'https://');
    }

    public function visitFullTagToken(FullTagToken $fullTagToken)
    {
        parent::visitFullTagToken($fullTagToken);
        if ($fullTagToken->isCloseTag() && !$fullTagToken->isSelfClosed()) {
            $depth = \count($this->tagStack);
            if (array_key_exists($depth+1, $this->globalConfigurationStack)) {
                unset($this->globalConfigurationStack[$depth-1]);
            }
            $this->resetActiveConfiguration();
        }
    }

    public function visitTag(TagToken $tagToken)
    {
        if (in_array($tagToken->getName(), ['wrap', 'nowrap'])) {
            if ($tagToken->getParent()->isStartTag()) {
                $this->pushConfiguration($tagToken);
                $this->setActiveConfiguration($tagToken);
            } elseif ($tagToken->getParent()->isCloseTag() && !$tagToken->getParent()->isSelfClosed()) {
                $depth = \count($this->tagStack);
                if (array_key_exists($depth, $this->localConfigurationStack)) {
                    unset($this->localConfigurationStack[$depth]);
                    $this->resetActiveConfiguration();
                }
            }
        }
    }

    public function visitEos(EosToken $eosToken)
    {
        $this->fillUp($eosToken);
    }

    /** @codeCoverageIgnore */
    public function visitDecoration(DecorationToken $decorationToken)
    {
        // do nothing
    }

    protected function addNewLine(TokenInterface $token)
    {
        $originalToken = $token;
        while (!$token->isFirst()) {
            $prev = $token->prevSibling();
            if ($prev->widthNextSibling()) {
                $token = $prev;
            } elseif ($prev instanceof SeparatorToken && $prev->isEmpty()) {
                $prev->remove();
                $this->cursor -= $prev->getLength();
                break;
            } else {
                break;
            }
        }
        $this->fillUp($token);
        if (!$token->isFirst()) {
            $token->insertBefore(new SeparatorToken("\n"));
        }
        $this->newLineReset($originalToken->prevSibling());
    }

    protected function newLineReset(TokenInterface $token = null)
    {
        $this->cursor = 0;
        if (null !== $token) {
            while ($token instanceof Token && !$token->isFirst() && !$this->tokenIsANewLineString($token)) {
                $this->cursor += $token->getLength();
                $token = $token->prevSibling();
            }
        }
    }

    protected function tokenIsANewLineString(TokenInterface $token)
    {
        return $token instanceof SeparatorToken && "\n" == $token->getOriginalStringRepresentation();
    }

    protected function resetActiveConfiguration()
    {
        $localDepth = $this->findLastConfigurationDepth($this->localConfigurationStack);
        if (null === $localDepth) {
            $localDepth = -1;
        }
        $globalDepth = $this->findLastConfigurationDepth($this->globalConfigurationStack);
        if (null === $globalDepth) {
            $globalDepth = -1;
        }
        if ($globalDepth >= 0 && $globalDepth > $localDepth) {
            $this->setActiveConfiguration($this->globalConfigurationStack[$globalDepth]);
        } elseif ($localDepth >= 0 && $localDepth >= $globalDepth) {
            $this->setActiveConfiguration($this->localConfigurationStack[$localDepth]);
        } else {
            $this->setActiveConfiguration(null);
        }
    }

    /**
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

    protected function setActiveConfiguration(TagToken $wrapToken = null)
    {
        $this->widthLimit = null;
        $this->wordCutLimit = null;
        $this->cutUrls = false;
        $this->fillUpString = null;

        if ($wrapToken && $wrapToken->getName() != 'nowrap') {
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
                        $this->widthLimit = $attrValue;
                        break;
                    case 'cut_words':
                        // If it is set without any value, we cut every words
                        if (null === $attrValue) {
                            $attrValue = 1;
                        }
                        $this->wordCutLimit = $attrValue;
                        break;
                    case 'cut_urls':
                        $this->cutUrls = true;
                        break;
                    case 'fill_up':
                        // If it is set without any value, we use ' ' (space)
                        if (null === $attrValue) {
                            $attrValue = ' ';
                        }
                        $this->fillUpString = $attrValue;
                        break;
                    default:
                        throw new \InvalidArgumentException(sprintf(
                            'Invalid configuration option: `%s`',
                            $attrName
                        ));
                }
            }
        }
    }

    protected function pushConfiguration(TagToken $wrapToken)
    {
        $depth = \count($this->tagStack);
        if ($wrapToken->getParent()->isSelfClosed()) {
            $this->globalConfigurationStack[$depth-1] = $wrapToken;
        } else {
            $this->localConfigurationStack[$depth] = $wrapToken;
        }
    }

    protected function getWidthLimit()
    {
        return $this->widthLimit;
    }

    protected function getWordCutLimit()
    {
        return null === $this->wordCutLimit ? $this->getWidthLimit() : $this->wordCutLimit;
    }

    protected function fillUp(TokenInterface $newLineBorderToken)
    {
        if ($this->fillUpString) {
            $missingChars = $this->getWidthLimit() - $this->cursor;
            if ($missingChars > 0 && $missingChars < $this->getWidthLimit()) {
                $patternLength = \mb_strlen($this->fillUpString);
                $fillUpChars = \mb_substr(str_repeat($this->fillUpString, ceil($missingChars/$patternLength)), -$missingChars);
                $newLineBorderToken->insertBefore(new DecorationToken($fillUpChars));
            }
        }
    }
}
