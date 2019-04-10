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

/**
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
interface TokenStreamInterface extends \Iterator, \Countable
{
    /**
     * Push a new token to end of the stream.
     *
     * @param TokenInterface $token
     */
    public function push(TokenInterface $token): void;

    /**
     * Pop the last token of the stream. Get null if the stream is empty.
     *
     * @return TokenInterface|null
     */
    public function pop(): ?TokenInterface;

    /**
     * Remove all children.
     */
    public function clean(): void;

    /**
     * Remove the current child.
     */
    public function removeCurrent(): void;

    /**
     * It gets the pointer to step back.
     */
    public function prev(): void;

    /**
     * Check the position of concrete token.
     *
     * @param TokenInterface $token
     *
     * @return bool
     */
    public function isFirst(TokenInterface $token): bool;

    /**
     * Check the position of concrete token.
     *
     * @param TokenInterface $token
     *
     * @return bool
     */
    public function isLast(TokenInterface $token): bool;

    /**
     * Get the previous child if it exists. If not, it will throw a TokenNotFoundException. It doesn't change the
     * position, just get the element!
     *
     * @param TokenInterface $token
     *
     * @return TokenInterface
     *
     * @throws TokenNotFoundException
     */
    public function getPrev(TokenInterface $token): TokenInterface;

    /**
     * Get the next child if it exists. If not, it will throw a TokenNotFoundException. It doesn't change the
     * position, just get the element!
     *
     * @param TokenInterface $token
     *
     * @return TokenInterface
     *
     * @throws TokenNotFoundException
     */
    public function getNext(TokenInterface $token): TokenInterface;

    /**
     * It inserts a new token AFTER the reference token. It needs to change the pointer if the current element moved.
     *
     * @param TokenInterface $referenceToken
     * @param TokenInterface $newToken
     */
    public function insertAfter(TokenInterface $referenceToken, TokenInterface $newToken): void;

    /**
     * It inserts a new token BEFORE the reference token. It needs to change the pointer if the current element moved.
     *
     * @param TokenInterface $referenceToken
     * @param TokenInterface $newToken
     */
    public function insertBefore(TokenInterface $referenceToken, TokenInterface $newToken): void;

    /**
     * Insert a new token after the current position.
     *
     * @param TokenInterface $token
     */
    public function insertAfterCurrent(TokenInterface $token): void;

    /**
     * Insert a new token before the current position.
     *
     * @param TokenInterface $token
     */
    public function insertBeforeCurrent(TokenInterface $token): void;
}
