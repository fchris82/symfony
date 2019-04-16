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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\Lexer;
use Symfony\Component\Console\Formatter\Visitors\HrefVisitor;
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

            $printVisitor = new PrintVisitor($full ? PrintVisitor::PRINT_DEBUG : PrintVisitor::PRINT_NORMAL);
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
            ['<href=idea://open/?file=/path/SomeFile.php&line=12>some URL</> no URL', 'some URL no URL', 'JetBrains-JediTerm'],
            ['<href=idea://open/?file=/path/SomeFile.php&line=12>some URL</href> no URL', "\e]8;;idea://open/?file=/path/SomeFile.php&line=12\e\\some URL\e]8;;\e\\ no URL"],
            // Unclosed
            ['<href=idea://open/?file=/path/SomeFile.php&line=12>some URL', "\e]8;;idea://open/?file=/path/SomeFile.php&line=12\e\\some URL\e]8;;\e\\"],
            // Unclosed with other tags
            ['<href=idea://open/?file=/path/SomeFile.php&line=12><comment>some URL</comment>', "\e]8;;idea://open/?file=/path/SomeFile.php&line=12\e\\some URL\e]8;;\e\\"],
        ];
    }
}
