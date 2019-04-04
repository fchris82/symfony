<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.02.
 * Time: 16:39
 */

namespace Symfony\Component\Console\Formatter\Tokens;

use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;

class FullTextToken extends TokenWithChildren
{
    public function accept(FormatterVisitorInterface $formatterVisitor)
    {
        $formatterVisitor->visitFullText($this);
    }
}
