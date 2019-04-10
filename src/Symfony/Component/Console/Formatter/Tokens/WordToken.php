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
 * Simple "string" token.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class WordToken extends Token
{
    public function __construct(string $originalStringRepresentation, Token $parent = null)
    {
        // Change the escaped \< sign to unescaped version
        $originalStringRepresentation = str_replace('\\<', '<', $originalStringRepresentation);
        parent::__construct($originalStringRepresentation, $parent);
    }

    public function accept(FormatterVisitorInterface $formatterVisitor): void
    {
        $formatterVisitor->visitWord($this);
    }
}
