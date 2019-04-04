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
            ['<href=idea://open/?file=/path/SomeFile.php&line=12>some URL</>', "\e]8;;idea://open/?file=/path/SomeFile.php&line=12\e\\some URL\e]8;;\e\\"],
            ['<href=idea://open/?file=/path/SomeFile.php&line=12>some URL</>', "some URL", 'JetBrains-JediTerm'],
        ];
    }
}
