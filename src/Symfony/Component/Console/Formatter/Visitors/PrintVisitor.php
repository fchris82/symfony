<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

/**
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class PrintVisitor extends AbstractVisitor implements OutputBuildVisitorInterface
{
    protected $full;
    protected $output;

    /**
     * @param bool $full If it is true, the hidden elements will be shown also. You can use it for debug.
     */
    public function __construct(bool $full = false)
    {
        $this->full = $full;
    }

    public function visitFullText(FullTextToken $fullTextToken): void
    {
        $this->output = '';
        parent::visitFullText($fullTextToken);
    }

    public function visitSeparator(SeparatorToken $separatorToken): void
    {
        $this->visit($separatorToken);
    }

    public function visitWord(WordToken $wordToken): void
    {
        $this->visit($wordToken);
    }

    public function visitFullTagToken(FullTagToken $fullTagToken): void
    {
        $this->visit($fullTagToken);
    }

    public function visitTag(TagToken $tagToken): void
    {
        $this->visit($tagToken);
    }

    public function visitEos(EosToken $eosToken): void
    {
        $this->visit($eosToken);
    }

    public function visitDecoration(DecorationToken $decorationToken): void
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

    public function getOutput(): string
    {
        return $this->output;
    }
}
