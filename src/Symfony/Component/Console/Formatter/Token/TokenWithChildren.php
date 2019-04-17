<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter\Token;

/**
 * It is a "special" token that has child tokens.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
abstract class TokenWithChildren extends Token implements \IteratorAggregate
{
    /**
     * @var TokenStreamInterface
     */
    protected $children;

    /**
     * TokenWithChildren constructor. In most of case the parent will set later (if it exists).
     *
     * @param string     $originalStringRepresentationRepresentation
     * @param Token|null $parent
     */
    public function __construct(string $originalStringRepresentationRepresentation, Token $parent = null)
    {
        parent::__construct($originalStringRepresentationRepresentation, $parent);
        $this->children = new TokenStream([]);
    }

    /**
     * Retrieve a TokenStreamInterface object.
     *
     * @see  http://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @return \Traversable|TokenStreamInterface An instance of an object implementing <b>Iterator</b> or
     *                                           <b>Traversable</b>
     */
    public function getIterator(): TokenStreamInterface
    {
        return $this->children;
    }

    public function removeChildren(TokenInterface $token): void
    {
        $this->children->removeToken($token);
    }

    /**
     * Push a new token to the end of the stream and set the parent.
     *
     * @param TokenInterface $token
     *
     * @return TokenInterface
     */
    public function push(TokenInterface $token): TokenInterface
    {
        $this->children->push($token);
        $token->setParent($this);

        return $this;
    }

    /**
     * Pop the last child. If the stream is empty, it will get null.
     *
     * @return TokenInterface|null
     */
    public function pop(): ?TokenInterface
    {
        return $this->children->pop();
    }

    /**
     * Remove all children.
     */
    public function clean(): void
    {
        $this->children->clean();
    }

    /**
     * {@inheritdoc}
     */
    public function getLength(): int
    {
        $length = 0;
        foreach ($this->getIterator() as $child) {
            $length += $child->getLength();
        }

        return $length;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $children = [];
        foreach ($this->children as $child) {
            $children[] = (string) $child;
        }

        return sprintf("%s(\n%s\n)", $this->typeToString(), implode("\n", $children));
    }
}
