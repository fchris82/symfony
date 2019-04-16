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
 * Decoration token for any decoration strings. Eg: `\033[32m`.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class DecorationToken extends WordToken
{
    public function accept(FormatterVisitorInterface $formatterVisitor): void
    {
        $formatterVisitor->visitDecoration($this);
    }
}
