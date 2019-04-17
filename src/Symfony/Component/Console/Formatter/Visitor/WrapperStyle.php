<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter\Visitor;

/**
 * Wrapping style.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class WrapperStyle implements WrapperStyleInterface
{
    /** @var int|null */
    protected $width;
    /** @var int|null */
    protected $wordCutLimit;
    /** @var bool */
    protected $cutUrls = false;
    /** @var string|null */
    protected $fillUpString;

    /**
     * @return self
     */
    public static function create(): WrapperStyleInterface
    {
        return new self();
    }

    /**
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @param int|null $width
     *
     * @return $this
     */
    public function setWidth(?int $width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getWordCutLimit(): ?int
    {
        return null === $this->wordCutLimit ? $this->getWidth() : $this->wordCutLimit;
    }

    /**
     * @param int|null $wordCutLimit
     *
     * @return $this
     */
    public function setWordCutLimit(?int $wordCutLimit)
    {
        $this->wordCutLimit = $wordCutLimit;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCutUrls(): bool
    {
        return $this->cutUrls;
    }

    /**
     * @param bool $cutUrls
     *
     * @return $this
     */
    public function setCutUrls(bool $cutUrls)
    {
        $this->cutUrls = $cutUrls;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFillUpString(): ?string
    {
        return $this->fillUpString;
    }

    /**
     * @param string|null $fillUpString
     *
     * @return $this
     */
    public function setFillUpString(?string $fillUpString)
    {
        $this->fillUpString = $fillUpString;

        return $this;
    }
}
