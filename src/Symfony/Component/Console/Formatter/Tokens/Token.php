<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.03.29.
 * Time: 13:54
 */

namespace Symfony\Component\Console\Formatter\Tokens;

abstract class Token implements TokenInterface
{
    /**
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
     * @param string $originalStringRepresentationRepresentation
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

    public function setParent(TokenInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Be careful with this method! The iterator gets it in the next, if you don't step forward! You can run into
     * infinite loop!
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

    public function insertBefore(TokenInterface $token): void
    {
        $token->setParent($this->getParent());
        $this->getParent()->getIterator()->insertBefore($this, $token);
    }

    public function getParent(): TokenWithChildren
    {
        return $this->parent;
    }

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
    }

    public function widthNextSibling(): bool
    {
        return false;
    }

    public function widthPreviousSibling(): bool
    {
        return false;
    }
}
