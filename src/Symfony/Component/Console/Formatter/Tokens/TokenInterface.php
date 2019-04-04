<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2019.04.02.
 * Time: 15:40
 */

namespace Symfony\Component\Console\Formatter\Tokens;


use Symfony\Component\Console\Formatter\Visitors\FormatterVisitorInterface;

interface TokenInterface
{
    public function getParent(): TokenWithChildren;
    public function setParent(TokenInterface $token);
    public function accept(FormatterVisitorInterface $formatterVisitor);
    public function getLength(): int;
    public function replace(TokenInterface $token): void;
    public function insertBefore(TokenInterface $token): void;
    public function insertAfter(TokenInterface $token): void;
    public function isFirst(): bool;
    public function isLast(): bool;
    public function prevSibling(): TokenInterface;
    public function nextSibling(): TokenInterface;
    public function remove(): void;
    public function widthPreviousSibling(): bool;
    public function widthNextSibling(): bool;
}
