<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\Formatter\Visitors;

use Symfony\Component\Console\Formatter\Lexer;
use Symfony\Component\Console\Formatter\Visitors\PrintVisitor;
use Symfony\Component\Console\Formatter\Visitors\WrapperVisitor;
use PHPUnit\Framework\TestCase;

class WrapperVisitorTest extends TestCase
{
    /**
     * @param string $input
     * @param string $output
     * @param bool   $full
     *
     * @dataProvider dpVisitFullText
     */
    public function testVisitFullText(string $input, string $output, bool $full = false)
    {
        $lexer = new Lexer();
        $fullText = $lexer->tokenize($input);
        $visitor = new WrapperVisitor();
        $fullText->accept($visitor);

        $printVisitor = new PrintVisitor($full ? PrintVisitor::PRINT_DEBUG : PrintVisitor::PRINT_NORMAL);
        $fullText->accept($printVisitor);

        $this->assertEquals($output, $printVisitor->getOutput());
    }

    public function dpVisitFullText()
    {
        return [
            ['', ''],
            ['<tag>word</tag>', '[TagToken<tag>]word[TagToken</tag>][EosToken()]', true],
            ['<wrap=2>word</wrap>', "wo\nrd"],
            ['<wrap=2/>word', "wo\nrd"],
            // UTF-8 test
            ['<wrap=8/>öüóőúéáű', "öüóőúéáű"],
            ['<wrap=4/>öüóőúéáű', "öüóő\núéáű"],
            ['<wrap=6,fill_up/>öüóőúéáű', "öüóőúé\náű    "],
            ['<wrap=6,fill_up:./>öüóőúéáű', "öüóőúé\náű...."],
            ['<wrap=6,fill_up,cut_words:0/>öüóőúéáű', "öüóőúéáű"],
            ['<wrap=6/>öüóő úéáű', "öüóő\núéáű"],
            ['<wrap=6,cut_words/>öüóő úéáű', "öüóő ú\néáű"],
            // Test two different wrapping
            ['<wrap=4>öüóőúéáű</wrap> thisisalongword', "öüóő\núéáű thisisalongword"],
            ['<wrap=10><comment><wrap=8/><wrap=4>öüóőúéáű</wrap> thisisalongword</comment> thisisanotherlongword</wrap>', "öüóő\núéáű\nthisisal\nongword\nthisisanot\nherlongwor\nd"],
            // Test wrap between tags. Every start and close tag should be same line with "their contents"
            [
                '<wrap=5/><tag0/><tag></tag><tag1>word1</tag1>word2<tag0/><tag2>word3</tag2>',
                <<<EOS
[TagToken<wrap=5/>][TagToken<tag0/>][TagToken<tag>][TagToken</tag>][TagToken<tag1>]word1[TagToken</tag1>]
word2
[TagToken<tag0/>][TagToken<tag2>]word3[TagToken</tag2>][EosToken()]
EOS
                , true
            ],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidAttribute()
    {
        $lexer = new Lexer();
        $fullText = $lexer->tokenize('<wrap=120,unknown>Test</wrap>');
        $visitor = new WrapperVisitor();
        $fullText->accept($visitor);
    }

    /**
     * @param string $inputFile
     * @param string $outputFile
     * @param string $formatting
     *
     * @dataProvider dpFiles
     */
    public function testVisitFullTextFromFiles($inputFile, $outputFile, $formatting = '')
    {
        $lexer = new Lexer();
        $fullText = $lexer->tokenize($formatting.$this->getInputContent($inputFile));
        $visitor = new WrapperVisitor();
        $fullText->accept($visitor);

        $printVisitor = new PrintVisitor(PrintVisitor::PRINT_DEBUG);
        $fullText->accept($printVisitor);

        $this->assertEquals($this->getOutputContent($outputFile), $printVisitor->getOutput());
    }

    public function dpFiles()
    {
        return [
            // Check simple text
            [
                'lipsum120.txt',
                'lipsum120.txt',
            ],
            // Check colored text
            [
                'lipsum_with_tags.txt',
                'lipsum_with_tags.txt',
            ],
            // Check long words
            [
                'with_long_words.txt',
                'with_long_words_with_default_cut.txt',
                "<wrap=30/>\n"
            ],
            [
                'with_long_words.txt',
                'with_long_words_without_cut.txt',
                "<wrap=30,cut_words:0/>\n",
            ],
            [
                'with_long_words.txt',
                'with_long_words_with_cut_all.txt',
                "<wrap=30,cut_words,cut_urls/>\n",
            ],
        ];
    }

    protected function getInputContent($fileName)
    {
        $filePath = __DIR__.'/../../Fixtures/Formatter/WordWrapper/input/'.$fileName;

        return file_get_contents($filePath);
    }

    protected function getOutputContent($fileName)
    {
        $filePath = __DIR__.'/../../Fixtures/Formatter/WordWrapper/output/'.$fileName;

        return file_get_contents($filePath) . '[EosToken()]';
    }
}
