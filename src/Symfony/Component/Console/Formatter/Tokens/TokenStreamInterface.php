<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.03.
 * Time: 11:29
 */

namespace Symfony\Component\Console\Formatter\Tokens;


interface TokenStreamInterface extends \Iterator, \Countable
{
    public function push(TokenInterface $token);
    public function pop(): TokenInterface;
    public function clean(): void;
    public function removeCurrent(): void;
    public function prev(): void;
    public function isFirst(TokenInterface $token): bool;
    public function isLast(TokenInterface $token): bool;
    public function getPrev(TokenInterface $token);
    public function getNext(TokenInterface $token);
    public function nextIf(callable $condition);
    public function insertAfter(TokenInterface $referenceToken, TokenInterface $newToken);
    public function insertBefore(TokenInterface $referenceToken, TokenInterface $newToken);
    public function insertAfterCurrent(TokenInterface $token);
    public function insertBeforeCurrent(TokenInterface $token);
}
