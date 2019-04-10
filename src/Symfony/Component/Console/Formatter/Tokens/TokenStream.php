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
 * Token stream, token container for collecting ordered tokens (children)
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class TokenStream implements TokenStreamInterface
{
    /**
     * @var array|Token[]
     */
    private $tokens;

    /**
     * @var int
     */
    private $current = 0;

    /**
     * TokenStream constructor.
     *
     * @param array|Token[] $tokens
     */
    public function __construct($tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * It is only for tests.
     *
     * @return string
     */
    public function __toString(): string
    {
        return implode("\n", $this->tokens);
    }

    /**
     * You can insert new tokens into the stream to set position. If the new position isn't greater than the current pointer
     * position it will get the pointer to step forward.
     *
     * @param array    $tokens
     * @param int|null $position
     */
    public function injectTokens(array $tokens, int $position = null): void
    {
        // set default position
        if (null === $position) {
            $position = $this->current;
        }
        // Insert the new tokens
        $this->tokens = array_merge(\array_slice($this->tokens, 0, $position), $tokens, \array_slice($this->tokens, $position));
        // Fix the position
        if ($position <= $this->current) {
            $this->current += count($tokens);
        }
    }

    /**
     * Insert a new token to set position.
     *
     * @param TokenInterface $token
     * @param int|null       $position
     */
    public function insert(TokenInterface $token, int $position = null): void
    {
        $this->injectTokens([$token], $position);
    }

    /**
     * {@inheritdoc}
     */
    public function insertAfterCurrent(TokenInterface $token): void
    {
        $this->injectTokens([$token], $this->current+1);
    }

    /**
     * {@inheritdoc}
     */
    public function insertBeforeCurrent(TokenInterface $token): void
    {
        $this->injectTokens([$token], $this->current);
        $this->current++;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAfter(TokenInterface $referenceToken, TokenInterface $newToken): void
    {
        $i = $this->findIndex($referenceToken);
        $this->insert($newToken, $i+1);
    }

    /**
     * {@inheritdoc}
     */
    public function insertBefore(TokenInterface $referenceToken, TokenInterface $newToken): void
    {
        $i = $this->findIndex($referenceToken);
        $this->insert($newToken, $i);
    }

    /**
     * Remove a concrete token.
     *
     * @param TokenInterface $token
     */
    public function removeToken(TokenInterface $token): void
    {
        $i = $this->findIndex($token);
        $this->remove($i);
    }

    /**
     * Remove a concrete position.
     *
     * @param int $i
     */
    protected function remove(int $i): void
    {
        $token = $this->tokens[$i];
        $token->setParent(null);
        unset($this->tokens[$i]);
        $this->tokens = array_values($this->tokens);
        if ($i <= $this->current) {
            $this->current--;
        }
    }

    /**
     * Count elements of an object
     *
     * @link  http://php.net/manual/en/countable.count.php
     *
     * @return int The custom count as an integer.
     */
    public function count(): int
    {
        return \count($this->tokens);
    }

    /**
     * Return the current element
     *
     * @link  http://php.net/manual/en/iterator.current.php
     *
     * @return Token
     */
    public function current(): Token
    {
        return $this->get($this->current);
    }

    /**
     * Move forward to next element
     *
     * @link  http://php.net/manual/en/iterator.next.php
     */
    public function next(): void
    {
        $this->current++;
    }

    /**
     * Return the key of the current element
     *
     * @link  http://php.net/manual/en/iterator.key.php
     *
     * @return int
     */
    public function key(): int
    {
        return $this->current;
    }

    /**
     * Checks if current position is valid
     *
     * @link  http://php.net/manual/en/iterator.valid.php
     *
     * @return boolean The return value will be casted to boolean and then evaluated.
     */
    public function valid(): bool
    {
        return array_key_exists($this->current, $this->tokens);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link  http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind(): void
    {
        $this->current = 0;
    }

    /**
     * Check the position.
     *
     * @param int $position
     *
     * @return bool
     */
    public function has(int $position): bool
    {
        return array_key_exists($position, $this->tokens);
    }

    /**
     * Get the set position.
     *
     * @param int $position
     *
     * @return Token
     *
     * @throws TokenNotFoundException
     */
    protected function get(int $position): Token
    {
        if (!$this->has($position)) {
            throw new TokenNotFoundException(sprintf('Invalid token index: `%s`', $position));
        }

        return $this->tokens[$position];
    }

    /**
     * It tries to find the position of the token. It uses the `spl_object_hash()` function!
     *
     * @param TokenInterface $token
     *
     * @return int
     *
     * @throws TokenNotFoundException
     */
    protected function findIndex(TokenInterface $token): int
    {
        // pre-check before foreach
        if ($this->current() === $token) {
            return $this->current;
        }
        foreach ($this->tokens as $i => $child) {
            if (spl_object_hash($child) == spl_object_hash($token)) {
                return $i;
            }
        }

        throw new TokenNotFoundException(sprintf('Token not found: `%s`', $token));
    }

    /**
     * {@inheritdoc}
     */
    public function push(TokenInterface $token): void
    {
        array_push($this->tokens, $token);
    }

    /**
     * {@inheritdoc}
     */
    public function pop(): ?TokenInterface
    {
        return array_pop($this->tokens);
    }

    /**
     * {@inheritdoc}
     */
    public function clean(): void
    {
        $this->tokens = [];
    }

    /**
     * {@inheritdoc}
     */
    public function removeCurrent(): void
    {
        unset($this->tokens[$this->current]);
        $this->tokens = array_values($this->tokens);
        $this->current--;
    }

    /**
     * {@inheritdoc}
     */
    public function prev(): void
    {
        $this->current--;
    }

    /**
     * {@inheritdoc}
     */
    public function isFirst(TokenInterface $token): bool
    {
        return $this->tokens[0] === $token;
    }

    /**
     * {@inheritdoc}
     */
    public function isLast(TokenInterface $token): bool
    {
        return $this->tokens[$this->count()-1] === $token;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrev(TokenInterface $token): TokenInterface
    {
        $index = $this->findIndex($token);
        return $this->get($index-1);
    }

    /**
     * {@inheritdoc}
     */
    public function getNext(TokenInterface $token): TokenInterface
    {
        $index = $this->findIndex($token);
        return $this->get($index+1);
    }
}
