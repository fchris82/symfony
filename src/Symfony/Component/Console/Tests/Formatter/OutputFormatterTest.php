<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\Formatter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class OutputFormatterTest extends TestCase
{
    public function testEmptyTag()
    {
        $formatter = new OutputFormatter(true);
        $this->assertEquals('foo<>bar', $formatter->format('foo<>bar'));
    }

    public function testLGCharEscaping()
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals('foo<bar', $formatter->format('foo\\<bar'));
        $this->assertEquals('foo << bar', $formatter->format('foo << bar'));
        $this->assertEquals('foo << bar \\', $formatter->format('foo << bar \\'));
        $this->assertEquals("foo << \033[32mbar \\ baz\033[39m \\", $formatter->format('foo << <info>bar \\ baz</info> \\'));
        $this->assertEquals('<info>some info</info>', $formatter->format('\\<info>some info\\</info>'));
        $this->assertEquals('\\<info>some info\\</info>', OutputFormatter::escape('<info>some info</info>'));

        $this->assertEquals(
            "\033[33mSymfony\\Component\\Console does work very well!\033[39m",
            $formatter->format('<comment>Symfony\Component\Console does work very well!</comment>')
        );
    }

    public function testBundledStyles()
    {
        $formatter = new OutputFormatter(true);

        $this->assertTrue($formatter->hasStyle('error'));
        $this->assertTrue($formatter->hasStyle('info'));
        $this->assertTrue($formatter->hasStyle('comment'));
        $this->assertTrue($formatter->hasStyle('question'));

        $this->assertEquals(
            "\033[37;41msome error\033[39;49m",
            $formatter->format('<error>some error</error>')
        );
        $this->assertEquals(
            "\033[32msome info\033[39m",
            $formatter->format('<info>some info</info>')
        );
        $this->assertEquals(
            "\033[33msome comment\033[39m",
            $formatter->format('<comment>some comment</comment>')
        );
        $this->assertEquals(
            "\033[30;46msome question\033[39;49m",
            $formatter->format('<question>some question</question>')
        );
    }

    public function testNestedStyles()
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals(
            "\033[37;41msome \033[39;49m\033[32msome info\033[39m\033[37;41m error\033[39;49m",
            $formatter->format('<error>some <info>some info</info> error</error>')
        );
    }

    public function testAdjacentStyles()
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals(
            "\033[37;41msome error\033[39;49m\033[32msome info\033[39m",
            $formatter->format('<error>some error</error><info>some info</info>')
        );
    }

    public function testStyleMatchingNotGreedy()
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals(
            "(\033[32m>=2.0,<2.3\033[39m)",
            $formatter->format('(<info>>=2.0,<2.3</info>)')
        );
    }

    public function testStyleEscaping()
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals(
            "(\033[32mz>=2.0,<<<a2.3\\\033[39m)",
            $formatter->format('(<info>'.$formatter->escape('z>=2.0,<\\<<a2.3\\').'</info>)')
        );

        $this->assertEquals(
            "\033[32m<error>some error</error>\033[39m",
            $formatter->format('<info>'.$formatter->escape('<error>some error</error>').'</info>')
        );
    }

    public function testDeepNestedStyles()
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals(
            "\033[37;41merror\033[39;49m\033[32minfo\033[39m\033[33mcomment\033[39m\033[37;41merror\033[39;49m",
            $formatter->format('<error>error<info>info<comment>comment</info>error</error>')
        );
    }

    public function testNewStyle()
    {
        $formatter = new OutputFormatter(true);

        $style = new OutputFormatterStyle('blue', 'white');
        $formatter->setStyle('test', $style);

        $this->assertEquals($style, $formatter->getStyle('test'));
        $this->assertNotEquals($style, $formatter->getStyle('info'));

        $style = new OutputFormatterStyle('blue', 'white');
        $formatter->setStyle('b', $style);

        $this->assertEquals("\033[34;47msome \033[39;49m\033[34;47mcustom\033[39;49m\033[34;47m msg\033[39;49m", $formatter->format('<test>some <b>custom</b> msg</test>'));
    }

    public function testRedefineStyle()
    {
        $formatter = new OutputFormatter(true);

        $style = new OutputFormatterStyle('blue', 'white');
        $formatter->setStyle('info', $style);

        $this->assertEquals("\033[34;47msome custom msg\033[39;49m", $formatter->format('<info>some custom msg</info>'));
    }

    public function testInlineStyle()
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals("\033[34;41msome text\033[39;49m", $formatter->format('<fg=blue;bg=red>some text</>'));
        $this->assertEquals("\033[34;41msome text\033[39;49m", $formatter->format('<fg=blue;bg=red>some text</fg=blue;bg=red>'));
    }

    /**
     * @param string      $tag
     * @param string|null $expected
     * @param string|null $input
     *
     * @dataProvider provideInlineStyleOptionsCases
     */
    public function testInlineStyleOptions($tag, $expected = null, $input = null)
    {
        $styleString = substr($tag, 1, -1);
        $formatter = new OutputFormatter(true);
        $method = new \ReflectionMethod($formatter, 'createStyleFromString');
        $method->setAccessible(true);
        $result = $method->invoke($formatter, $styleString);
        if (null === $expected) {
            $this->assertFalse($result);
            $expected = $tag.$input.'</'.$styleString.'>';
            $this->assertSame($expected, $formatter->format($expected));
        } else {
            /* @var OutputFormatterStyle $result */
            $this->assertInstanceOf(OutputFormatterStyle::class, $result);
            $this->assertSame($expected, $formatter->format($tag.$input.'</>'));
            $this->assertSame($expected, $formatter->format($tag.$input.'</'.$styleString.'>'));
        }
    }

    public function provideInlineStyleOptionsCases()
    {
        return [
            ['<unknown=_unknown_>'],
            ['<unknown=_unknown_;a=1;b>'],
            ['<fg=green;>', "\033[32m[test]\033[39m", '[test]'],
            ['<fg=green;bg=blue;>', "\033[32;44ma\033[39;49m", 'a'],
            ['<fg=green;options=bold>', "\033[32;1mb\033[39;22m", 'b'],
            ['<fg=green;options=reverse;>', "\033[32;7m<a>\033[39;27m", '<a>'],
            ['<fg=green;options=bold,underscore>', "\033[32;1;4mz\033[39;22;24m", 'z'],
            ['<fg=green;options=bold,underscore,reverse;>', "\033[32;1;4;7md\033[39;22;24;27m", 'd'],
        ];
    }

    public function provideInlineStyleTagsWithUnknownOptions()
    {
        return [
            ['<options=abc;>', 'abc'],
            ['<options=abc,def;>', 'abc'],
            ['<fg=green;options=xyz;>', 'xyz'],
            ['<fg=green;options=efg,abc>', 'efg'],
        ];
    }

    public function testNonStyleTag()
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals("\033[32msome \033[39m\033[32m<tag>\033[39m\033[32m \033[39m\033[32m<setting=value>\033[39m\033[32m styled \033[39m\033[32m<p>\033[39m\033[32msingle-char tag\033[39m\033[32m</p>\033[39m", $formatter->format('<info>some <tag> <setting=value> styled <p>single-char tag</p></info>'));
    }

    public function testFormatLongString()
    {
        $formatter = new OutputFormatter(true);
        $long = str_repeat('\\', 14000);
        $this->assertEquals("\033[37;41msome error\033[39;49m".$long, $formatter->format('<error>some error</error>'.$long));
    }

    public function testFormatToStringObject()
    {
        $formatter = new OutputFormatter(false);
        $this->assertEquals(
            'some info', $formatter->format(new TableCell())
        );
    }

    public function testFormatterHasStyles()
    {
        $formatter = new OutputFormatter(false);

        $this->assertTrue($formatter->hasStyle('error'));
        $this->assertTrue($formatter->hasStyle('info'));
        $this->assertTrue($formatter->hasStyle('comment'));
        $this->assertTrue($formatter->hasStyle('question'));
    }

    /**
     * @dataProvider provideDecoratedAndNonDecoratedOutput
     */
    public function testNotDecoratedFormatter(string $input, string $expectedNonDecoratedOutput, string $expectedDecoratedOutput, string $terminalEmulator = 'foo')
    {
        $prevTerminalEmulator = getenv('TERMINAL_EMULATOR');
        putenv('TERMINAL_EMULATOR='.$terminalEmulator);

        try {
            $this->assertEquals($expectedDecoratedOutput, (new OutputFormatter(true))->format($input));
            $this->assertEquals($expectedNonDecoratedOutput, (new OutputFormatter(false))->format($input));
        } finally {
            putenv('TERMINAL_EMULATOR'.($prevTerminalEmulator ? "=$prevTerminalEmulator" : ''));
        }
    }

    public function provideDecoratedAndNonDecoratedOutput()
    {
        return [
            ['<error>some error</error>', 'some error', "\033[37;41msome error\033[39;49m"],
            ['<info>some info</info>', 'some info', "\033[32msome info\033[39m"],
            ['<comment>some comment</comment>', 'some comment', "\033[33msome comment\033[39m"],
            ['<question>some question</question>', 'some question', "\033[30;46msome question\033[39;49m"],
            ['<fg=red>some text with inline style</>', 'some text with inline style', "\033[31msome text with inline style\033[39m"],
            ['<href=idea://open/?file=/path/SomeFile.php&line=12>some URL</>', 'some URL', "\033]8;;idea://open/?file=/path/SomeFile.php&line=12\033\\some URL\033]8;;\033\\"],
            ['<href=idea://open/?file=/path/SomeFile.php&line=12>some URL</>', 'some URL', 'some URL', 'JetBrains-JediTerm'],
        ];
    }

    public function testContentWithLineBreaks()
    {
        $formatter = new OutputFormatter(true);

        $this->assertEquals(<<<EOF
\033[32m
some text\033[39m
EOF
            , $formatter->format(<<<'EOF'
<info>
some text</info>
EOF
            ));

        $this->assertEquals(<<<EOF
\033[32msome text
\033[39m
EOF
            , $formatter->format(<<<'EOF'
<info>some text
</info>
EOF
            ));

        $this->assertEquals(<<<EOF
\033[32m
some text
\033[39m
EOF
            , $formatter->format(<<<'EOF'
<info>
some text
</info>
EOF
            ));

        $this->assertEquals(<<<EOF
\033[32m
some text
more text
\033[39m
EOF
            , $formatter->format(<<<'EOF'
<info>
some text
more text
</info>
EOF
            ));
    }

    public function testFormatAndWrap()
    {
        $formatter = new OutputFormatter(true);

        $this->assertSame("fo\no\e[37;41mb\e[39;49m\n\e[37;41mar\e[39;49m\nba\nz", $formatter->formatAndWrap('foo<error>bar</error> baz', 2));
        $this->assertSame("pr\ne \e[37;41m\e[39;49m\n\e[37;41mfo\e[39;49m\n\e[37;41mo \e[39;49m\n\e[37;41mba\e[39;49m\n\e[37;41mr \e[39;49m\n\e[37;41mba\e[39;49m\n\e[37;41mz\e[39;49m \npo\nst", $formatter->formatAndWrap('pre <error>foo bar baz</error> post', 2));
        $this->assertSame("pre\e[37;41m\e[39;49m\n\e[37;41mfoo\e[39;49m\n\e[37;41mbar\e[39;49m\n\e[37;41mbaz\e[39;49m\npos\nt", $formatter->formatAndWrap('pre <error>foo bar baz</error> post', 3));
        $this->assertSame("pre \e[37;41m\e[39;49m\n\e[37;41mfoo \e[39;49m\n\e[37;41mbar \e[39;49m\n\e[37;41mbaz\e[39;49m \npost", $formatter->formatAndWrap('pre <error>foo bar baz</error> post', 4));
        $this->assertSame("pre \e[37;41mf\e[39;49m\n\e[37;41moo ba\e[39;49m\n\e[37;41mr baz\e[39;49m\npost", $formatter->formatAndWrap('pre <error>foo bar baz</error> post', 5));
        $this->assertSame("Lore\nm \e[37;41mip\e[39;49m\n\e[37;41msum\e[39;49m \ndolo\nr \e[32msi\e[39m\n\e[32mt\e[39m am\net", $formatter->formatAndWrap('Lorem <error>ipsum</error> dolor <info>sit</info> amet', 4));
        $this->assertSame("Lorem \e[37;41mip\e[39;49m\n\e[37;41msum\e[39;49m dolo\nr \e[32msit\e[39m am\net", $formatter->formatAndWrap('Lorem <error>ipsum</error> dolor <info>sit</info> amet', 8));
        $this->assertSame("Lorem \e[37;41mipsum\e[39;49m dolor \e[32m\e[39m\n\e[32msit\e[39m, \e[37;41mamet\e[39;49m et \e[32mlauda\e[39m\n\e[32mntium\e[39m architecto", $formatter->formatAndWrap('Lorem <error>ipsum</error> dolor <info>sit</info>, <error>amet</error> et <info>laudantium</info> architecto', 18));

        $formatter = new OutputFormatter();

        $this->assertSame("fo\nob\nar\nba\nz", $formatter->formatAndWrap('foo<error>bar</error> baz', 2));
        $this->assertSame("pr\ne \nfo\no \nba\nr \nba\nz \npo\nst", $formatter->formatAndWrap('pre <error>foo bar baz</error> post', 2));
        $this->assertSame("pre\nfoo\nbar\nbaz\npos\nt", $formatter->formatAndWrap('pre <error>foo bar baz</error> post', 3));
        $this->assertSame("pre \nfoo \nbar \nbaz \npost", $formatter->formatAndWrap('pre <error>foo bar baz</error> post', 4));
        $this->assertSame("pre f\noo ba\nr baz\npost", $formatter->formatAndWrap('pre <error>foo bar baz</error> post', 5));
    }
}

class TableCell
{
    public function __toString()
    {
        return '<info>some info</info>';
    }
}
