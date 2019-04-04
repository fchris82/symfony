<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.03.29.
 * Time: 16:05
 */

namespace Symfony\Component\Console\Formatter\Tokens;

use Symfony\Component\Console\Exception\FormatterTokenNotFoundException;

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

    public function __toString()
    {
        return implode("\n", $this->tokens);
    }

    public function injectTokens(array $tokens, $position = null)
    {
        if (null === $position) {
            $position = $this->current;
        }
        $this->tokens = array_merge(\array_slice($this->tokens, 0, $position), $tokens, \array_slice($this->tokens, $position));
        if ($position <= $this->current) {
            $this->current += count($tokens);
        }
    }

    public function insert(TokenInterface $token, $position = null)
    {
        $this->injectTokens([$token], $position);
    }

    public function insertAfterCurrent(TokenInterface $token)
    {
        $this->injectTokens([$token], $this->current+1);
    }

    public function insertBeforeCurrent(TokenInterface $token)
    {
        $this->injectTokens([$token], $this->current);
        $this->current++;
    }

    public function insertAfter(TokenInterface $referenceToken, TokenInterface $newToken)
    {
        $i = $this->findIndex($referenceToken);
        $this->insert($newToken, $i+1);
    }

    public function insertBefore(TokenInterface $referenceToken, TokenInterface $newToken)
    {
        $i = $this->findIndex($referenceToken);
        $this->insert($newToken, $i);
    }

    public function removeToken(TokenInterface $token)
    {
        $i = $this->findIndex($token);
        $this->remove($i);
    }

    protected function remove(int $i)
    {
        unset($this->tokens[$i]);
        $this->tokens = array_values($this->tokens);
        $this->current--;
    }

    /**
     * Count elements of an object
     *
     * @link  http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return \count($this->tokens);
    }

    /**
     * Return the current element
     *
     * @link  http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current(): Token
    {
        return $this->get($this->current);
    }

    /**
     * Move forward to next element
     *
     * @link  http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->current++;
    }

    /**
     * Return the key of the current element
     *
     * @link  http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key(): int
    {
        return $this->current;
    }

    /**
     * Checks if current position is valid
     *
     * @link  http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return array_key_exists($this->current, $this->tokens);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link  http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->current = 0;
    }

    public function has($position)
    {
        return array_key_exists($position, $this->tokens);
    }

    protected function get(int $position)
    {
        if (!$this->has($position)) {
            throw new FormatterTokenNotFoundException(sprintf('Invalid token index: `%s`', $position));
        }

        return $this->tokens[$position];
    }

    protected function findIndex(TokenInterface $token)
    {
        if ($this->current() === $token) {
            return $this->current;
        }
        foreach ($this->tokens as $i => $child) {
            if (spl_object_hash($child) == spl_object_hash($token)) {
                return $i;
            }
        }

        throw new FormatterTokenNotFoundException(sprintf('Token not found: `%s`', $token));
    }

    public function push(TokenInterface $token)
    {
        array_push($this->tokens, $token);
    }

    public function pop(): TokenInterface
    {
        return array_pop($this->tokens);
    }

    public function clean(): void
    {
        $this->tokens = [];
    }

    public function removeCurrent(): void
    {
        unset($this->tokens[$this->current]);
        $this->tokens = array_values($this->tokens);
        $this->current--;
    }

    public function prev(): void
    {
        $this->current--;
    }

    public function isFirst(TokenInterface $token): bool
    {
        return $this->tokens[0] === $token;
    }

    public function isLast(TokenInterface $token): bool
    {
        return $this->tokens[$this->count()-1] === $token;
    }

    public function getPrev(TokenInterface $token): TokenInterface
    {
        $index = $this->findIndex($token);
        return $this->get($index-1);
    }

    public function getNext(TokenInterface $token): TokenInterface
    {
        $index = $this->findIndex($token);
        return $this->get($index+1);
    }

    public function nextIf(callable $condition)
    {
        if (call_user_func($condition, $this->current(), $this->get($this->current+1))) {
            $this->next();
        }
    }
}
