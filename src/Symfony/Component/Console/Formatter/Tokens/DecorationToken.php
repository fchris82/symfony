<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.03.
 * Time: 14:56
 */

namespace Symfony\Component\Console\Formatter\Tokens;


use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;

class DecorationToken extends WordToken
{
    public function accept(FormatterVisitorInterface $formatterVisitor)
    {
        $formatterVisitor->visitDecoration($this);
    }
}
