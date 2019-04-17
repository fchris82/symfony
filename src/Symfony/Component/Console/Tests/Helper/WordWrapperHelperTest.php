<?php

namespace Symfony\Component\Console\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\Visitor\WrapperStyle;
use Symfony\Component\Console\Helper\WordWrapperHelper;

/**
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class WordWrapperHelperTest extends TestCase
{
    /**
     * @param string           $input
     * @param int|WrapperStyle $styleOrWidth
     * @param string           $output
     *
     * @dataProvider dpWordwrap
     */
    public function testWordwrap($input, $styleOrWidth, $output)
    {
        $wordWrapper = new WordWrapperHelper();
        $response = $wordWrapper->wordwrap($input, $styleOrWidth);

        $this->assertEquals($output, $response);
    }

    /**
     * Maybe in the future it should behave differently from wordwrap() function that is why it same the other now.
     *
     * @param string           $input
     * @param int|WrapperStyle $styleOrWidth
     * @param string           $output
     *
     * @dataProvider dpWordwrap
     */
    public function testStaticWrap($input, $styleOrWidth, $output)
    {
        $response = WordWrapperHelper::wrap($input, $styleOrWidth);

        $this->assertEquals($output, $response);
    }

    public function dpWordwrap()
    {
        $fillUpStyle = WrapperStyle::create()->setFillUpString(' ');

        return [
            // Check empty
            ['', 2, ''],
            ['', $fillUpStyle->setWidth(2), '  '],
            ["\n", 2, "\n"],
            ["\n", $fillUpStyle->setWidth(2), "  \n  "],
            // Check limit and UTF-8
            [
                'öüóőúéáű',
                8,
                'öüóőúéáű',
            ],
            [
                'öüóőúéáű',
                (clone $fillUpStyle)->setWidth(4),
                "öüóő\núéáű",
            ],
            [
                'öüóőúéáű',
                (clone $fillUpStyle)->setWidth(6),
                "öüóőúé\náű    ",
            ],
            // UTF-8 + tags
            [
                '<error>öüóőúéáű</error>',
                (clone $fillUpStyle)->setWidth(8),
                '<error>öüóőúéáű</error>',
            ],
            [
                'öüó<error>őú</error>éáű',
                (clone $fillUpStyle)->setWidth(8),
                'öüó<error>őú</error>éáű',
            ],
            [
                'foo <error>bar</error> baz',
                (clone $fillUpStyle)->setWidth(3),
                implode("\n", ['foo', '<error>bar</error>', 'baz']),
            ],
            [
                'foo <error>bar</error> baz',
                (clone $fillUpStyle)->setWidth(2),
                implode("\n", ['fo', 'o ', '<error>ba', 'r</error> ', 'ba', 'z ']),
            ],
            // Escaped tags
            [
                'foo \<error>bar\</error> baz',
                (clone $fillUpStyle)->setWidth(10),
                implode("\n", ['foo       ', '\<error>bar', '\</error>  ', 'baz       ']),
            ],
            [
                'foo \<error>bar\</error> baz',
                (clone $fillUpStyle)->setWidth(3),
                implode("\n", ['foo', '<er', 'ror', '>  ', 'bar', '</e', 'rro', 'r> ', 'baz']),
            ],
            [
                'foo<error>bar</error>baz foo',
                (clone $fillUpStyle)->setWidth(3),
                implode("\n", ['foo', '<error>bar</error>', 'baz', 'foo']),
            ],
            [
                'foo<error>bar</error>baz foo',
                (clone $fillUpStyle)->setWidth(2),
                implode("\n", ['fo', 'o<error>b', 'ar</error>', 'ba', 'z ', 'fo', 'o ']),
            ],
        ];
    }
}
