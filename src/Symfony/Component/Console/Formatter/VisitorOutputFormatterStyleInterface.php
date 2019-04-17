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
 * Formatter style interface for defining styles.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
interface VisitorOutputFormatterStyleInterface extends OutputFormatterStyleInterface
{
    /**
     * Init style decoration, the \033[*m strings.
     *
     * @return string
     */
    public function start(): string;

    /**
     * Close style decoration, the \033[*m strings.
     *
     * @return string
     */
    public function close(): string;
}
