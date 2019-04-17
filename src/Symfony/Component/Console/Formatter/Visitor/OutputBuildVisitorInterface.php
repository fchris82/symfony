<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter\Visitor;

/**
 * Use this interface for visitors that create outputs from token stream.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
interface OutputBuildVisitorInterface
{
    public function getOutput(): string;
}
