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

use Symfony\Component\Console\Formatter\Tokens\DecorationToken;
use Symfony\Component\Console\Formatter\Tokens\EosToken;
use Symfony\Component\Console\Formatter\Tokens\FullTagToken;
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
    const PRINT_NORMAL = 0;
    const PRINT_RAW = 1;
    const PRINT_RAW_ESCAPED = 2;
    const PRINT_DEBUG = 3;

    protected $mode;
    protected $output;

    /**
     * @param int $mode
     */
    public function __construct(int $mode = self::PRINT_NORMAL)
    {
        $this->mode = $mode;
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

    /**
     * @param Token|TokenWithChildren $token
     */
    protected function visit(Token $token)
    {
        if ($this->tokenNeedsHandleChildren($token)) {
            /** @var TokenInterface $child */
            foreach ($token->getIterator() as $child) {
                $child->accept($this);
            }
        } else {
            switch ($this->mode) {
                case self::PRINT_NORMAL:
                    if ($token->getLength()) {
                        $this->output .= $token->getOriginalStringRepresentation();
                    }
                    break;
                case self::PRINT_RAW_ESCAPED:
                    // Escaping "tag" words which aren't real tags.
                    if ($token->getLength()
                        && '<' == substr($token->getOriginalStringRepresentation(), 0, 1)
                        && '>' == substr($token->getOriginalStringRepresentation(), -1)
                    ) {
                        $this->output .= '\\';
                    }
                    // There isn't break here, it is correct!
                    // no break
                case self::PRINT_RAW:
                    $this->output .= $token->getOriginalStringRepresentation();
                    break;
                case self::PRINT_DEBUG:
                    $this->output .= $token->getLength()
                        ? $token->getOriginalStringRepresentation()
                        : '['.(string) $token.']';
                    break;
            }
        }
    }

    protected function tokenNeedsHandleChildren(Token $token)
    {
        if (\in_array($this->mode, [self::PRINT_RAW, self::PRINT_RAW_ESCAPED]) && $token instanceof FullTagToken) {
            return false;
        }

        return $token instanceof TokenWithChildren && $token->getIterator()->count();
    }

    public function getOutput(): string
    {
        return $this->output;
    }
}
