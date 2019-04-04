<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.03.
 * Time: 20:00
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

class StyleVisitor extends AbstractVisitor implements DecoratorVisitorInterface
{
    /** @var array|OutputFormatterStyle[] */
    protected $styles = [];
    protected $styleStack;
    /** @var OutputFormatterStyleInterface */
    protected $currentStyle;

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

    public function setStyle(string $name, OutputFormatterStyleInterface $style)
    {
        $this->styles[strtolower($name)] = $style;
    }

    public function hasStyle(string $name): bool
    {
        return isset($this->styles[strtolower($name)]);
    }

    public function getStyle(string $name): OutputFormatterStyle
    {
        if (!$this->hasStyle($name)) {
            throw new InvalidArgumentException(sprintf('Undefined style: %s', $name));
        }

        return $this->styles[strtolower($name)];
    }

    public function visitFullText(FullTextToken $fullTextToken)
    {
        parent::visitFullText($fullTextToken);
    }

    /**
     * @param SeparatorToken $separatorToken
     */
    public function visitSeparator(SeparatorToken $separatorToken)
    {
        // We close every line and start a new line
        if ($this->styleStack->count() && "\n" == $separatorToken->getOriginalStringRepresentation()) {
            $currentStyle = $this->styleStack->getCurrent();
            $separatorToken->insertBefore(new DecorationToken($currentStyle->close()));
            $separatorToken->insertAfter(new DecorationToken($currentStyle->start()));
        }
    }

    public function visitWord(WordToken $wordToken)
    {
        // do nothing
    }

    public function visitFullTagToken(FullTagToken $fullTagToken)
    {
        if ($fullTagToken->isStartTag()) {
            array_push($this->tagStack, $fullTagToken);
        }

        $iterator = $fullTagToken->getIterator();
        for ($iterator->rewind();$iterator->valid();$iterator->next()) {
            $iterator->current()->accept($this);
        }

        if (null !== $this->currentStyle) {
            if ($this->styleStack->count() > 0) {
                $prev = $this->styleStack->getCurrent();
                $fullTagToken->insertBefore(new DecorationToken($prev->close()));
            }
            $this->styleStack->push(\count($this->tagStack), $this->currentStyle);
            $fullTagToken->insertAfter(new DecorationToken($this->currentStyle->start()));
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

    public function visitTag(TagToken $tagToken)
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

    public function visitEos(EosToken $eosToken)
    {
        while ($this->styleStack->count()) {
            $style = $this->styleStack->pop();
            $eosToken->insertBefore(new DecorationToken($style->close()));
        }
    }

    public function visitDecoration(DecorationToken $decorationToken)
    {
        // do nothing
    }
}
