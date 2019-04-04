<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.03.29.
 * Time: 12:53
 */

namespace Symfony\Component\Console\Formatter;


use Symfony\Component\Console\Exception\FormatterToLargeInputException;
use Symfony\Component\Console\Formatter\Tokens\EosToken;
use Symfony\Component\Console\Formatter\Tokens\FullTextToken;
use Symfony\Component\Console\Formatter\Tokens\SeparatorToken;
use Symfony\Component\Console\Formatter\Tokens\WordToken;
use Symfony\Component\Console\Formatter\Tokens\FullTagToken;
use Symfony\Component\Console\Helper\Helper;

class Lexer
{
    protected $text;
    protected $cursor;
    protected $end;
    /** @var FullTextToken */
    protected $fullTextToken;

    /**
     * @param $text
     *
     * @return FullTextToken
     */
    public function tokenize($text)
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $cursor = 0;
        // Don't use here mb_* functions! The PREG_OFFSET_CAPTURE give use "byte" value!
        $end = \strlen($text);
        // 240x55 = 13200
        if ($end > 13200 && \substr_count($text, ' ') > 10000) {
            throw new FormatterToLargeInputException('The text, what you want to format, is too large. If you really want to format it, you should slice it.');
        }
        $this->fullTextToken = new FullTextToken($text);
        $pattern = sprintf('{\\\\?<((%1$s)|/(%1$s)?)>}ix', Helper::FORMAT_TAG_REGEX);
        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $i => $match) {
            $pos = $match[1];
            $tag = $match[0];

            // Add the text up to the next tag.
            // Don't replace it to mb_* function!
            $textBlock = \substr($text, $cursor, $pos - $cursor);
            if ($textBlock) {
                $this->tokenizeTextBlock($textBlock);
            }
            $cleanTag = ltrim($tag, '\\');
            // It is an escaped tag:
            // Don't replace it to mb_* function!
            if (\strlen($tag) != \strlen($cleanTag)) {
                $this->fullTextToken->push(new WordToken($cleanTag));
            } else {
                $this->fullTextToken->push(new FullTagToken($cleanTag));
            }
            // Don't replace it to mb_* function!
            $cursor = $pos + \strlen($tag);
        }

        if ($cursor != $end) {
            // Don't replace it to mb_* function!
            $lastBlock = \substr($text, $cursor);
            if ($lastBlock) {
                $this->tokenizeTextBlock($lastBlock);
            }
        }

        $this->fullTextToken->push(new EosToken());

        return $this->fullTextToken;
    }

    private function tokenizeTextBlock($textBlock)
    {
        $cursor = 0;
        // Don't use here mb_* functions! The PREG_OFFSET_CAPTURE give use "byte" value!
        $end = \strlen($textBlock);
        $pattern = sprintf('{\s}u', Helper::FORMAT_TAG_REGEX);
        preg_match_all($pattern, $textBlock, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $i => $match) {
            $pos = $match[1];
            $separator = $match[0];

            // Add the text up to the next tag.
            // Don't replace it to mb_* function!
            $word = \substr($textBlock, $cursor, $pos - $cursor);
            if ($word) {
                $this->fullTextToken->push(new WordToken($word));
            }
            $this->fullTextToken->push(new SeparatorToken($separator));
            // Don't replace it to mb_* function!
            $cursor = $pos + \strlen($separator);
        }

        if ($cursor != $end) {
            // Don't replace it to mb_* function!
            $lastWord = \substr($textBlock, $cursor);
            if ($lastWord) {
                $this->fullTextToken->push(new WordToken($lastWord));
            }
        }
    }
}
