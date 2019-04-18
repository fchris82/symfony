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

use Symfony\Component\Console\Formatter\Token\DecorationToken;
use Symfony\Component\Console\Formatter\Token\EosToken;
use Symfony\Component\Console\Formatter\Token\FullTagToken;
use Symfony\Component\Console\Formatter\Token\FullTextToken;
use Symfony\Component\Console\Formatter\Token\SeparatorToken;
use Symfony\Component\Console\Formatter\Token\TagToken;
use Symfony\Component\Console\Formatter\Token\WordToken;

/**
 * Interface for formatter visitors.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
interface FormatterVisitorInterface
{
    public function iterate(iterable $tokens);
}
