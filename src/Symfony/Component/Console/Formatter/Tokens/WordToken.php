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
    public function accept(FormatterVisitorInterface $formatterVisitor)
    {
        $formatterVisitor->visitWord($this);
    }
}
