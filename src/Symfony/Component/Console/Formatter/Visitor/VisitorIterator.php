<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter\Visitor;

/**
 * An iterator for collecting and ordering visitors. You can set priority. The higher will run before the lower priority.
 *
 * @author Krisztián Ferenczi <ferenczi.krisztian@gmail.com>
 */
class VisitorIterator implements \Iterator, \Countable
{
    /**
     * 2 dimensional array. First key is the priority, the second it a simple order from 0.
     *
     * @var array
     */
    protected $visitorsByPriority = [];

    /**
     * 1 dimensional array, it is generated from `$visitorsByPriority` property.
     *
     * @var array|FormatterVisitorInterface[]
     *
     * @see VisitorIterator::sort()
     */
    protected $sortedVisitorsCache = [];

    /** @var int */
    protected $current;

    /**
     * Insert a new visitor to the "chain".
     *
     * @param FormatterVisitorInterface $value
     * @param int                       $priority
     */
    public function insert(FormatterVisitorInterface $value, int $priority = 0): void
    {
        if (!\array_key_exists($priority, $this->visitorsByPriority)) {
            $this->visitorsByPriority[$priority] = [];
        }

        $this->visitorsByPriority[$priority][] = $value;
        $this->sort();
    }

    protected function sort(): void
    {
        krsort($this->visitorsByPriority, SORT_NUMERIC);
        $this->sortedVisitorsCache = [];
        foreach ($this->visitorsByPriority as $priority => $subVisitors) {
            foreach ($subVisitors as $visitor) {
                $this->sortedVisitorsCache[] = $visitor;
            }
        }
    }

    /**
     * Return the current element.
     *
     * @see  http://php.net/manual/en/iterator.current.php
     *
     * @return FormatterVisitorInterface
     */
    public function current(): FormatterVisitorInterface
    {
        return $this->sortedVisitorsCache[$this->current];
    }

    /**
     * Move forward to next element.
     *
     * @see  http://php.net/manual/en/iterator.next.php
     */
    public function next(): void
    {
        ++$this->current;
    }

    /**
     * Return the key of the current element.
     *
     * @see  http://php.net/manual/en/iterator.key.php
     *
     * @return int
     */
    public function key(): int
    {
        return $this->current;
    }

    /**
     * Checks if current position is valid.
     *
     * @see  http://php.net/manual/en/iterator.valid.php
     *
     * @return bool The return value will be casted to boolean and then evaluated.
     *              Returns true on success or false on failure.
     *
     * @since 5.0.0
     */
    public function valid()
    {
        return \array_key_exists($this->current, $this->sortedVisitorsCache);
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @see  http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind(): void
    {
        $this->current = 0;
    }

    /**
     * Count elements of an object.
     *
     * @see  http://php.net/manual/en/countable.count.php
     *
     * @return int the custom count as an integer
     */
    public function count(): int
    {
        return \count($this->sortedVisitorsCache);
    }
}
