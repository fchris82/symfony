<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.01.
 * Time: 16:41
 */

namespace Symfony\Component\Console\Formatter\Tokens;

use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;

class WordToken extends Token
{
    public function __construct(string $originalStringRepresentation, Token $parent = null)
    {
        $originalStringRepresentation = str_replace('\\<', '<', $originalStringRepresentation);
        parent::__construct($originalStringRepresentation, $parent);
    }

    public function accept(FormatterVisitorInterface $formatterVisitor)
    {
        $formatterVisitor->visitWord($this);
    }
}
