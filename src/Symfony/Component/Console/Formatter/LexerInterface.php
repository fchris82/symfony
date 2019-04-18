<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter;

/**
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
interface LexerInterface
{
    /**
     * Tokenize a text.
     *
     * @param string $text
     *
     * @return iterable
     */
    public function tokenize(string $text): iterable;
}
