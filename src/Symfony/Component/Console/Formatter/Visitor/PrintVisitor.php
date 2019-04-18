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
use Symfony\Component\Console\Formatter\Token\TokenWithChildren;
use Symfony\Component\Console\Formatter\Token\WordToken;

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

    public function iterate(iterable $tokens)
    {
        $this->output = '';
        parent::iterate($tokens);
    }

    /**
     * @param $type
     * @param string|Token $token
     */
    protected function handleToken($type, $token)
    {
        if ($token instanceof Token && $this->tokenNeedsHandleChildren($token)) {
            /** @var TokenInterface $child */
            foreach ($token->getIterator() as $child) {
//                $child->accept($this);
            }
        } else {
            $string = is_string($token) ? $token : $token->getOriginalStringRepresentation();
            $mustShow = is_string($token) || $token->getLength() > 0;
            switch ($this->mode) {
                case self::PRINT_NORMAL:
                    if ($mustShow) {
                        $this->output .= $string;
                    }
                    break;
                case self::PRINT_RAW_ESCAPED:
                    // Escaping "tag" words which aren't real tags.
                    if ($mustShow
                        && '<' == substr($string, 0, 1)
                        && '>' == substr($string, -1)
                    ) {
                        $this->output .= '\\';
                    }
                    $this->output .= $string;
                    break;
                case self::PRINT_RAW:
                    $this->output .= $string;
                    break;
                case self::PRINT_DEBUG:
                    $this->output .= $mustShow
                        ? $string
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

    protected function handleWord(string $value)
    {
        // do nothing
    }

    protected function handleSeparator(string $value)
    {
        // do nothing
    }

    protected function handleTag(TagToken $tagToken)
    {
        // do nothing
    }

    protected function handleDecoration(string $value)
    {
        // do nothing
    }

    protected function handleEos(EosToken $eosToken)
    {
        // do nothing
    }
}
