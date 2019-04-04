<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.03.
 * Time: 15:50
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
use Symfony\Component\Console\Formatter\Tokens\TokenWithChildren;
use Symfony\Component\Console\Formatter\Tokens\WordToken;

class PrintVisitor extends AbstractVisitor
{
    protected $full;
    protected $output;

    public function __construct(bool $full = false)
    {
        $this->full = $full;
    }

    public function visitFullText(FullTextToken $fullTextToken)
    {
        $this->output = '';
        parent::visitFullText($fullTextToken);
    }

    public function visitSeparator(SeparatorToken $separatorToken)
    {
        $this->visit($separatorToken);
    }

    public function visitWord(WordToken $wordToken)
    {
        $this->visit($wordToken);
    }

    public function visitFullTagToken(FullTagToken $fullTagToken)
    {
        $this->visit($fullTagToken);
    }

    public function visitTag(TagToken $tagToken)
    {
        $this->visit($tagToken);
    }

    public function visitEos(EosToken $eosToken)
    {
        $this->visit($eosToken);
    }

    public function visitDecoration(DecorationToken $decorationToken)
    {
        $this->visit($decorationToken);
    }

    protected function visit(Token $token)
    {
        if ($token instanceof TokenWithChildren && $token->getIterator()->count()) {
            /** @var TokenInterface $child */
            foreach ($token->getIterator() as $child) {
                $child->accept($this);
            }
        } elseif ($token->getLength()) {
            $this->output .= $token->getOriginalStringRepresentation();
        } elseif ($this->full) {
            $this->output .= '[' . (string) $token . ']';
        }
    }

    public function getOutput()
    {
        return $this->output;
    }
}
