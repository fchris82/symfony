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

/**
 * Abstract token class.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
abstract class Token implements TokenInterface
{
    /**
     * The original string representation without any modification.
     *
     * @var string
     */
    protected $originalStringRepresentation;

    /**
     * @var null|TokenWithChildren
     */
    protected $parent;

    /**
     * Token constructor.
     *
     * @param string     $originalStringRepresentationRepresentation
     * @param Token|null $parent
     */
    public function __construct(string $originalStringRepresentationRepresentation, Token $parent = null)
    {
        $this->originalStringRepresentation = $originalStringRepresentationRepresentation;
        $this->parent = $parent;
    }

    public function __toString(): string
    {
        return sprintf('%s(%s)', $this->typeToString(), $this->originalStringRepresentation);
    }

    public function typeToString(): string
    {
        $path = explode('\\', get_class($this));

        return array_pop($path);
    }

    /**
     * @return string
     */
    public function getOriginalStringRepresentation(): string
    {
        return $this->originalStringRepresentation;
    }

    public function setParent(TokenWithChildren $parent = null): TokenInterface
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Be careful with this method! The iterator gets it in the next, if you don't step forward! You can run into
     * infinite loop!
     *
     * Insert a token after this.
     *
     * @param TokenInterface $token
     *
     * @internal
     */
    public function insertAfter(TokenInterface $token): void
    {
        $token->setParent($this->getParent());
        $this->getParent()->getIterator()->insertAfter($this, $token);
    }

    /**
     * Insert a token before this.
     *
     * @param TokenInterface $token
     */
    public function insertBefore(TokenInterface $token): void
    {
        $token->setParent($this->getParent());
        $this->getParent()->getIterator()->insertBefore($this, $token);
    }

    public function getParent(): TokenWithChildren
    {
        return $this->parent;
    }

    /**
     * Default it is the string length, but sometimes it is 0.
     *
     * @return int
     */
    public function getLength(): int
    {
        return \mb_strlen($this->originalStringRepresentation);
    }

    public function isFirst(): bool
    {
        return $this->getParent()->getIterator()->isFirst($this);
    }

    public function isLast(): bool
    {
        return $this->getParent()->getIterator()->isFirst($this);
    }

    public function nextSibling(): TokenInterface
    {
        return $this->getParent()->getIterator()->getNext($this);
    }

    public function prevSibling(): TokenInterface
    {
        return $this->getParent()->getIterator()->getPrev($this);
    }

    public function replace(TokenInterface $token): void
    {
        $this->insertAfter($token);
        $this->remove();
    }

    public function remove(): void
    {
        $this->parent->removeChildren($this);
        $this->parent = null;
    }

    public function keepTogetherWithNextSibling(): bool
    {
        return false;
    }

    public function keepTogetherWithPreviousSibling(): bool
    {
        return false;
    }
}
