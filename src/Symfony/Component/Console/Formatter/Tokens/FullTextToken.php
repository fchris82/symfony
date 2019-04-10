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
 * This is a Full Text "token", with children tokens.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class FullTextToken extends TokenWithChildren
{
    public function accept(FormatterVisitorInterface $formatterVisitor): void
    {
        $formatterVisitor->visitFullText($this);
    }
}
