<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.04.
 * Time: 13:17
 */

namespace Symfony\Component\Console\Formatter\Visitors;


class VisitorStack extends \SplPriorityQueue
{
    protected $serial = PHP_INT_MAX;

    public function insert($value, $priority) {
        parent::insert($value, array($priority, $this->serial--));
    }
}
