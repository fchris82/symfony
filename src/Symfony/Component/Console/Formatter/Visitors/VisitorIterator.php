<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.04.
 * Time: 13:17
 */

namespace Symfony\Component\Console\Formatter\Visitors;


class VisitorIterator implements \Iterator, \Countable
{
    protected $visitorsByPriority = [];
    protected $sortedVisitorsCache = [];
    protected $current;

    public function insert($value, $priority) {
        if (!array_key_exists($priority, $this->visitorsByPriority)) {
            $this->visitorsByPriority[$priority] = [];
        }

        $this->visitorsByPriority[$priority][] = $value;
        $this->sort();
    }

    protected function sort()
    {
        krsort($this->visitorsByPriority);
        $this->sortedVisitorsCache = [];
        foreach ($this->visitorsByPriority as $priority => $subVisitors) {
            foreach ($subVisitors as $visitor) {
                $this->sortedVisitorsCache[] = $visitor;
            }
        }
    }

    /**
     * Return the current element
     *
     * @link  http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->sortedVisitorsCache[$this->current];
    }

    /**
     * Move forward to next element
     *
     * @link  http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->current++;
    }

    /**
     * Return the key of the current element
     *
     * @link  http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->current;
    }

    /**
     * Checks if current position is valid
     *
     * @link  http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return array_key_exists($this->current, $this->sortedVisitorsCache);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link  http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->current = 0;
    }

    /**
     * Count elements of an object
     *
     * @link  http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return \count($this->sortedVisitorsCache);
    }
}
