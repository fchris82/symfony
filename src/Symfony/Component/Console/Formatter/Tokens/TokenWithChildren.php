<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.03.
 * Time: 15:03
 */

namespace Symfony\Component\Console\Formatter\Tokens;


abstract class TokenWithChildren extends Token implements \IteratorAggregate
{
    /**
     * @var TokenStreamInterface
     */
    protected $children;

    public function __construct(string $originalStringRepresentationRepresentation, Token $parent = null)
    {
        parent::__construct($originalStringRepresentationRepresentation, $parent);
        $this->children = new TokenStream([]);
    }

    /**
     * Retrieve an external iterator
     *
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator(): TokenStreamInterface
    {
        return $this->children;
    }

    public function removeChildren(TokenInterface $token): void
    {
        $this->children->removeToken($token);
    }

    public function push(TokenInterface $token): TokenInterface
    {
        $this->children->push($token);
        $token->setParent($this);

        return $this;
    }

    public function pop(): TokenInterface
    {
        return $this->children->pop();
    }

    public function clean()
    {
        $this->children->clean();
    }

    public function getLength(): int
    {
        $length = 0;
        foreach ($this->getIterator() as $child) {
            $length += $child->getLength();
        }

        return $length;
    }

    public function __toString(): string
    {
        $children = [];
        foreach ($this->children as $child) {
            $children[] = (string) $child;
        }
        return sprintf("%s(\n%s\n)", $this->typeToString(), implode("\n", $children));
    }
}
