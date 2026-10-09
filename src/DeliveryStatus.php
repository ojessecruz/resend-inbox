<?php

declare(strict_types=1);

namespace Jessecruz\ResendInbox;

/**
 * Delivery state of an email sent from the inbox, fed by Resend webhooks.
 * Webhooks can arrive out of order, so a status never moves back to a lower
 * rank.
 */
enum DeliveryStatus: string
{
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Bounced = 'bounced';
    case Complained = 'complained';

    public static function fromWebhookType(string $type): ?self
    {
        return match ($type) {
            'email.sent' => self::Sent,
            'email.delivered' => self::Delivered,
            'email.bounced' => self::Bounced,
            'email.complained' => self::Complained,
            default => null,
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Sent => 1,
            self::Delivered => 2,
            self::Bounced, self::Complained => 3,
        };
    }

    /**
     * Whether this status may replace the stored one: the first status always
     * does, later ones only when they rank higher.
     */
    public function advancesFrom(?self $current): bool
    {
        return $current === null || $this->rank() > $current->rank();
    }

    public function isFailure(): bool
    {
        return $this === self::Bounced || $this === self::Complained;
    }
}
