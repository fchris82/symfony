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

use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;

/**
 * Tag token is a "sub token"/children of the FullTagToken. Structure:
 *
 *      <tag1=option1,option2:value2>
 *       ^^^^ ^^^^^^^^^^^^^^^^^^^^^^
 *       name          value
 *
 *      Values: ['option1', 'option2:value2']
 *
 * @see FullTagToken
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class TagToken extends Token
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $value;

    /** @var array|string[] */
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
            $this->value = $value;
            $this->values = explode(
                ',',
                $value
            );
        }
    }

    public function accept(FormatterVisitorInterface $formatterVisitor): void
    {
        $formatterVisitor->visitTag($this);
    }

    /**
     * Tokens are invisible so length of every token is 0.
     *
     * @return int
     */
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

    /**
     * @param string $tagString
     *
     * @return TagToken
     */
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
