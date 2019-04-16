<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter\Visitors;

/**
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
interface WrapperStyleInterface
{
    public function getWidth(): ?int;

    public static function create(): self;
}
