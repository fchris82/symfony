<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.08.
 * Time: 11:22
 */

namespace Symfony\Component\Console\Formatter\Visitors;


interface OutputBuildVisitorInterface
{
    public function getOutput(): string;
}
