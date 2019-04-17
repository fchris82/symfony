<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter\Token;

use Symfony\Component\Console\Formatter\Visitor\FormatterVisitorInterface;

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
