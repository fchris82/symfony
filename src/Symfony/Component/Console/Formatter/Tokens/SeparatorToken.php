<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.01.
 * Time: 17:12
 */

namespace Symfony\Component\Console\Formatter\Tokens;

use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;

class SeparatorToken extends Token
{
    public function accept(FormatterVisitorInterface $formatterVisitor)
    {
        $formatterVisitor->visitSeparator($this);
    }

    public function isEmpty(): bool
    {
        if (in_array($this->originalStringRepresentation, [' '])) {
            return true;
        }

        return false;
    }
}
