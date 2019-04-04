<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.03.29.
 * Time: 17:02
 */

namespace Symfony\Component\Console\Formatter\Tokens;

use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;

class TagToken extends Token
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * TagToken constructor.
     *
     * @param string $originalStringRepresentationRepresentation
     * @param string $name
     * @param string $value
     */
    public function __construct(string $originalStringRepresentationRepresentation, string $name, string $value = '')
    {
        parent::__construct($originalStringRepresentationRepresentation);
        $this->name = trim($name, '/');
        if ($value) {
            $this->value;
            $this->values = explode(
                ',',
                $value
            );
        }
    }

    public function accept(FormatterVisitorInterface $formatterVisitor)
    {
        $formatterVisitor->visitTag($this);
    }

    public function getLength(): int
    {
        return 0;
    }

    public function __toString(): string
    {
        $parent = $this->getParent();
        $prefix = $parent->isCloseTag() && !$parent->isSelfClosed() ? '/' : '';
        $suffix = $parent->isSelfClosed() ? '/' : '';
        return sprintf('%s<%s%s%s>', $this->typeToString(), $prefix, $this->getOriginalStringRepresentation(), $suffix);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return FullTagToken
     */
    public function getParent(): TokenWithChildren
    {
        return parent::getParent();
    }

    public static function parse(string $tagString): self
    {
        if (\strpos($tagString, '=') !== false) {
            list($name, $value) = explode('=', $tagString, 2);
        } else {
            $name = $tagString;
            $value = '';
        }

        return new self($tagString, $name, $value);
    }
}
