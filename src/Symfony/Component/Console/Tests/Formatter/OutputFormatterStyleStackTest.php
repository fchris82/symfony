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
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyleStack;

class OutputFormatterStyleStackTest extends TestCase
{
    public function testPush()
    {
        $stack = new OutputFormatterStyleStack();
        $stack->push(1, $s1 = new OutputFormatterStyle('white', 'black'));
        $stack->push(2, $s2 = new OutputFormatterStyle('yellow', 'blue'));

        $this->assertEquals($s2, $stack->getCurrent());

        $stack->push(3, $s3 = new OutputFormatterStyle('green', 'red'));

        $this->assertEquals($s3, $stack->getCurrent());
    }

    public function testPop()
    {
        $stack = new OutputFormatterStyleStack();
        $stack->push(1, $s1 = new OutputFormatterStyle('white', 'black'));
        $stack->push(2, $s2 = new OutputFormatterStyle('yellow', 'blue'));

        $this->assertEquals($s2, $stack->pop());
        $this->assertEquals($s1, $stack->pop());

        $stack->push(1, $s1 = new OutputFormatterStyle('white', 'black'));
        $stack->push(2, $s2 = new OutputFormatterStyle('yellow', 'blue'));

        $this->assertEquals(null, $stack->pop(3));
        $this->assertEquals($s1, $stack->pop(1));
    }

    public function testPopEmpty()
    {
        $stack = new OutputFormatterStyleStack();
        $style = new OutputFormatterStyle();

        $this->assertEquals($style, $stack->pop());
    }

    public function testPopNotLast()
    {
        $stack = new OutputFormatterStyleStack();
        $stack->push(1, $s1 = new OutputFormatterStyle('white', 'black'));
        $stack->push(2, $s2 = new OutputFormatterStyle('yellow', 'blue'));
        $stack->push(3, $s3 = new OutputFormatterStyle('green', 'red'));
        $stack->push(4, $s3 = new OutputFormatterStyle('magenta', 'cyan'));

        $this->assertEquals($s3, $stack->popByStyle($s3));
        $this->assertEquals($s1, $stack->pop(1));
        $this->assertEquals(new OutputFormatterStyle(), $stack->pop());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidPop()
    {
        $stack = new OutputFormatterStyleStack();
        $stack->push(1, new OutputFormatterStyle('white', 'black'));
        $stack->popByStyle(new OutputFormatterStyle('yellow', 'blue'));
    }
}
