<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.03.
 * Time: 19:46
 */

namespace Symfony\Component\Console\Tests\Formatter\Visitors;

use Symfony\Component\Console\Formatter\Lexer;
use Symfony\Component\Console\Formatter\Visitors\HrefVisitor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\Visitors\PrintVisitor;

class HrefVisitorTest extends TestCase
{
    /**
     * @param string $input
     * @param string $output
     * @param string $terminalEmulator
     * @param bool   $full
     *
     * @dataProvider dpVisitFullText
     */
    public function testVisitFullText(string $input, string $output, string $terminalEmulator = 'foo', bool $full = false)
    {
        $prevTerminalEmulator = getenv('TERMINAL_EMULATOR');
        putenv('TERMINAL_EMULATOR='.$terminalEmulator);

        try {
            $lexer = new Lexer();
            $fullText = $lexer->tokenize($input);
            $visitor = new HrefVisitor();
            $fullText->accept($visitor);

            $printVisitor = new PrintVisitor($full);
            $fullText->accept($printVisitor);

            $this->assertEquals($output, $printVisitor->getOutput());
        } finally {
            putenv('TERMINAL_EMULATOR'.($prevTerminalEmulator ? "=$prevTerminalEmulator" : ''));
        }
    }

    public function dpVisitFullText()
    {
        return [
            ['', ''],
            ['<href=idea://open/?file=/path/SomeFile.php&line=12>some URL</> no URL', "\e]8;;idea://open/?file=/path/SomeFile.php&line=12\e\\some URL\e]8;;\e\\ no URL"],
            ['<href=idea://open/?file=/path/SomeFile.php&line=12>some URL</> no URL', "some URL no URL", 'JetBrains-JediTerm'],
            ['<href=idea://open/?file=/path/SomeFile.php&line=12>some URL</href> no URL', "\e]8;;idea://open/?file=/path/SomeFile.php&line=12\e\\some URL\e]8;;\e\\ no URL"],
            // Unclosed
            ['<href=idea://open/?file=/path/SomeFile.php&line=12>some URL', "\e]8;;idea://open/?file=/path/SomeFile.php&line=12\e\\some URL\e]8;;\e\\"],
            // Unclosed with other tags
            ['<href=idea://open/?file=/path/SomeFile.php&line=12><comment>some URL</comment>', "\e]8;;idea://open/?file=/path/SomeFile.php&line=12\e\\some URL\e]8;;\e\\"],
        ];
    }
}
