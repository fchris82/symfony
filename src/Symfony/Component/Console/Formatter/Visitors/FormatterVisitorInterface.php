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

use Symfony\Component\Console\Formatter\Tokens\FullTagToken;
use Symfony\Component\Console\Formatter\Tokens\DecorationToken;
use Symfony\Component\Console\Formatter\Tokens\EosToken;
use Symfony\Component\Console\Formatter\Tokens\FullTextToken;
use Symfony\Component\Console\Formatter\Tokens\SeparatorToken;
use Symfony\Component\Console\Formatter\Tokens\TagToken;
use Symfony\Component\Console\Formatter\Tokens\WordToken;

/**
 * Interface for formatter visitors.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
interface FormatterVisitorInterface
{
    public function visitFullText(FullTextToken $fullTextToken): void;
    public function visitSeparator(SeparatorToken $separatorToken): void;
    public function visitWord(WordToken $wordToken): void;
    public function visitFullTagToken(FullTagToken $fullTagToken): void;
    public function visitTag(TagToken $tagToken): void;
    public function visitEos(EosToken $eosToken): void;
    public function visitDecoration(DecorationToken $decorationToken): void;
}
