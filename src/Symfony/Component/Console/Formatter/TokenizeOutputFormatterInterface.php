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
interface TokenizeOutputFormatterInterface extends OutputFormatterInterface
{
    /**
     * Get string without decoration. It could have better performance than fully tokenize.
     *
     * @param string $str
     *
     * @return string
     */
    public function removeDecoration($str);
}
