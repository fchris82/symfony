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

use Symfony\Component\Console\Exception\TokenNotFoundException;
use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;

/**
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
interface TokenInterface
{
    public function getParent(): TokenWithChildren;
    public function setParent(TokenWithChildren $token): self;

    /**
     * Accept a Visitor
     *
     * @param FormatterVisitorInterface $formatterVisitor
     */
    public function accept(FormatterVisitorInterface $formatterVisitor): void;

    /**
     * Get the length. It is used eg. in WrapperVisitor where we need the token length to calculate the position of
     * line breaks.
     *
     * @return int
     */
    public function getLength(): int;

    /**
     * Replace this token to an other $token. This token will be removed!
     *
     * @param TokenInterface $token
     */
    public function replace(TokenInterface $token): void;

    /**
     * Insert a new token BEFORE THIS into the parent.
     *
     * @param TokenInterface $token
     */
    public function insertBefore(TokenInterface $token): void;

    /**
     * Insert a new token AFTER THIS into the parent.
     *
     * @param TokenInterface $token
     */
    public function insertAfter(TokenInterface $token): void;

    /**
     * Is this first child? In the parent, not the root!
     *
     * @return bool
     */
    public function isFirst(): bool;

    /**
     * Is this last child? In the parent, not the root!
     *
     * @return bool
     */
    public function isLast(): bool;

    /**
     * Get the previous sibling if it exists. It throws TokenNotFoundException if sibling doesn't. It doesn't step
     * forward in the parent iterator!
     *
     * @return TokenInterface
     *
     * @throws TokenNotFoundException
     */
    public function prevSibling(): TokenInterface;

    /**
     * Get the next sibling if it exists. It throws TokenNotFoundException if sibling doesn't. It doesn't step
     * forward in the parent iterator!
     *
     * @return TokenInterface
     *
     * @throws TokenNotFoundException
     */
    public function nextSibling(): TokenInterface;

    /**
     * Remove this child from the parent.
     */
    public function remove(): void;

    /**
     * Line breaking rule: if it needs to keep together with the previous sibling, a new line character can't be
     * included between them.
     *
     * @return bool
     */
    public function keepTogetherWithPreviousSibling(): bool;

    /**
     * Line breaking rule: if it needs to kepp together with the next sibling, a new line character can't be included
     * between them.
     *
     * @return bool
     */
    public function keepTogetherWithNextSibling(): bool;
}
