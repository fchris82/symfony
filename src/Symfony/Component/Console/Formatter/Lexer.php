<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter;

use Symfony\Component\Console\Exception\FormatterTooLargeInputException;
use Symfony\Component\Console\Formatter\Tokens\EosToken;
use Symfony\Component\Console\Formatter\Tokens\FullTagToken;
use Symfony\Component\Console\Formatter\Tokens\FullTextToken;
use Symfony\Component\Console\Formatter\Tokens\SeparatorToken;
use Symfony\Component\Console\Formatter\Tokens\WordToken;
use Symfony\Component\Console\Helper\Helper;

/**
 * Tokenize a custom text to handle styling and wrapping it.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class Lexer implements LexerInterface
{
    /** @var FullTextToken */
    protected $fullTextToken;

    /**
     * @param $text
     *
     * @return FullTextToken
     */
    public function tokenize(string $text): \IteratorAggregate
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $cursor = 0;
        // Don't use mb_* functions here! The PREG_OFFSET_CAPTURE gives us "byte" value!
        $end = \strlen($text);
        // 240x55 = 13200
        if ($end > 13200 && \substr_count($text, ' ') > 5000) {
            throw new FormatterTooLargeInputException('The text, what you want to format, is too large. If you really want to format it, you should slice it.');
        }
        $this->fullTextToken = new FullTextToken($text);
        $pattern = sprintf('{\\\\?<((%1$s)|/(%1$s)?)>}ix', Helper::FORMAT_TAG_REGEX);
        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $i => $match) {
            $pos = $match[1];
            $tag = $match[0];

            // Add the text up to the next tag.
            // Don't replace it to mb_* function! The PREG_OFFSET_CAPTURE gives us "byte" value!
            $textBlock = \substr($text, $cursor, $pos - $cursor);
            if ('' != $textBlock) {
                $this->tokenizeTextBlock($textBlock);
            }
            $cleanTag = ltrim($tag, '\\');
            // It is an escaped tag:
            // Don't replace it to mb_* function! The PREG_OFFSET_CAPTURE gives us "byte" value!
            if (\strlen($tag) != \strlen($cleanTag)) {
                $this->fullTextToken->push(new WordToken($cleanTag));
            } else {
                $this->fullTextToken->push(new FullTagToken($cleanTag));
            }
            // Don't replace it to mb_* function! The PREG_OFFSET_CAPTURE gives us "byte" value!
            $cursor = $pos + \strlen($tag);
        }

        if ($cursor != $end) {
            // Don't replace it to mb_* function! The PREG_OFFSET_CAPTURE gives us "byte" value!
            $lastBlock = \substr($text, $cursor);
            if ('' != $lastBlock) {
                $this->tokenizeTextBlock($lastBlock);
            }
        }

        $this->fullTextToken->push(new EosToken());

        return $this->fullTextToken;
    }

    protected function tokenizeTextBlock($textBlock)
    {
        $cursor = 0;
        // Don't use mb_* functions here! The PREG_OFFSET_CAPTURE gives us "byte" value!
        $end = \strlen($textBlock);
        preg_match_all('{\s}u', $textBlock, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $i => $match) {
            $pos = $match[1];
            $separator = $match[0];

            // Add the text up to the next tag.
            // Don't replace it to mb_* function! The PREG_OFFSET_CAPTURE gives us "byte" value!
            $word = \substr($textBlock, $cursor, $pos - $cursor);
            if ('' != $word) {
                $this->fullTextToken->push(new WordToken($word));
            }
            $this->fullTextToken->push(new SeparatorToken($separator));
            // Don't replace it to mb_* function! The PREG_OFFSET_CAPTURE gives us "byte" value!
            $cursor = $pos + \strlen($separator);
        }

        if ($cursor != $end) {
            // Don't replace it to mb_* function! The PREG_OFFSET_CAPTURE gives us "byte" value!
            $lastWord = \substr($textBlock, $cursor);
            if ('' != $lastWord) {
                $this->fullTextToken->push(new WordToken($lastWord));
            }
        }
    }
}
