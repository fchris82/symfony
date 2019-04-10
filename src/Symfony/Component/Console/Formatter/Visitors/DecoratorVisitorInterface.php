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
 * Use this interface for decorator visitors. A visitor is a decorator visitor if it needs to run when decoration is
 * enabled, and it mustn't run when decoration is disabled.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
interface DecoratorVisitorInterface
{
}
