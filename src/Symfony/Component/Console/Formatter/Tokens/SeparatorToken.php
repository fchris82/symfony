<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter\Tokens;

use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;

/**
 * Separator string token. Every token is 1 char length in normal situations. Every char which fit with the \s regular
 * expression. Eg:
 *  - space: ' '
 *  - tab: "\t"
 *  - new line or carriage return: "\n", "\r".
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class SeparatorToken extends Token
{
    public function accept(FormatterVisitorInterface $formatterVisitor): void
    {
        $formatterVisitor->visitSeparator($this);
    }

    /**
     * While we try to wrap the text, the "empty" characters will be thrown away from the end of the lines before new
     * line character.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        if (\in_array($this->originalStringRepresentation, [' '])) {
            return true;
        }

        return false;
    }
}
