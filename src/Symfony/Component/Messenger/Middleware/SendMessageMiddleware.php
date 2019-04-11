<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\Middleware;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Stamp\ForceCallHandlersStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Samuel Roze <samuel.roze@gmail.com>
 * @author Tobias Schultze <http://tobion.de>
 *
 * @experimental in 4.2
 */
class SendMessageMiddleware implements MiddlewareInterface
{
    use LoggerAwareTrait;

    private $sendersLocator;
    private $eventDispatcher;

    public function __construct(SendersLocatorInterface $sendersLocator, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->sendersLocator = $sendersLocator;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $context = [
            'message' => $envelope->getMessage(),
            'class' => \get_class($envelope->getMessage()),
        ];

        $handle = false;
        $sender = null;

        try {
            if ($envelope->all(ReceivedStamp::class)) {
                // it's a received message, do not send it back
                $this->logger->info('Received message "{class}"', $context);
            } else {
                /** @var RedeliveryStamp|null $redeliveryStamp */
                $redeliveryStamp = $envelope->last(RedeliveryStamp::class);

                // dispatch event unless this is a redelivery
                $shouldDispatchEvent = null === $redeliveryStamp;
                foreach ($this->sendersLocator->getSenders($envelope, $handle) as $alias => $sender) {
                    // on redelivery, only deliver to the given sender
                    if (null !== $redeliveryStamp && !$redeliveryStamp->shouldRedeliverToSender(\get_class($sender), $alias)) {
                        continue;
                    }

                    if (null !== $this->eventDispatcher && $shouldDispatchEvent) {
                        $event = new SendMessageToTransportsEvent($envelope);
                        $this->eventDispatcher->dispatch($event);
                        $envelope = $event->getEnvelope();
                        $shouldDispatchEvent = false;
                    }

                    $this->logger->info('Sending message "{class}" with "{sender}"', $context + ['sender' => \get_class($sender)]);
                    $envelope = $sender->send($envelope->with(new SentStamp(\get_class($sender), \is_string($alias) ? $alias : null)));
                }

                // if the message was marked (usually by SyncTransport) that it handlers
                // MUST be called, mark them to be handled.
                if (null !== $envelope->last(ForceCallHandlersStamp::class)) {
                    $handle = true;
                }

                // on a redelivery, only send back to queue: never call local handlers
                if (null !== $redeliveryStamp) {
                    $handle = false;
                }
            }

            if (null === $sender || $handle) {
                return $stack->next()->handle($envelope, $stack);
            }
        } catch (\Throwable $e) {
            $context['exception'] = $e;
            $this->logger->warning('An exception occurred while handling message "{class}": '.$e->getMessage(), $context);

            throw $e;
        }

        // message should only be sent and not be handled by the next middleware
        return $envelope;
    }
}
