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
 * EOS is an abbreviation: End Of String. It is used for mark the end of full text.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class EosToken extends Token
{
    /**
     * EosToken constructor.
     */
    public function __construct()
    {
        parent::__construct('');
    }

    public function accept(FormatterVisitorInterface $formatterVisitor): void
    {
        $formatterVisitor->visitEos($this);
    }

    public function getLength(): int
    {
        return 0;
    }
}
