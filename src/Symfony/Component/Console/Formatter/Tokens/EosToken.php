<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.01.
 * Time: 16:57
 */

namespace Symfony\Component\Console\Formatter\Tokens;

use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;
use Traversable;

class EosToken extends Token
{
    /**
     * EosToken constructor.
     */
    public function __construct()
    {
        parent::__construct('');
    }

    public function accept(FormatterVisitorInterface $formatterVisitor)
    {
        $formatterVisitor->visitEos($this);
    }

    public function getLength(): int
    {
        return 0;
    }
}
