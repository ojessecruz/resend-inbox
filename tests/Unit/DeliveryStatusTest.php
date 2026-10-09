<?php

declare(strict_types=1);

use Jessecruz\ResendInbox\DeliveryStatus;

it('maps webhook types to statuses', function () {
    expect(DeliveryStatus::fromWebhookType('email.delivered'))->toBe(DeliveryStatus::Delivered)
        ->and(DeliveryStatus::fromWebhookType('email.complained'))->toBe(DeliveryStatus::Complained)
        ->and(DeliveryStatus::fromWebhookType('email.opened'))->toBeNull();
});

it('only moves forward', function () {
    expect(DeliveryStatus::Sent->advancesFrom(null))->toBeTrue()
        ->and(DeliveryStatus::Delivered->advancesFrom(DeliveryStatus::Sent))->toBeTrue()
        ->and(DeliveryStatus::Bounced->advancesFrom(DeliveryStatus::Delivered))->toBeTrue()
        ->and(DeliveryStatus::Sent->advancesFrom(DeliveryStatus::Delivered))->toBeFalse()
        ->and(DeliveryStatus::Bounced->advancesFrom(DeliveryStatus::Bounced))->toBeFalse()
        ->and(DeliveryStatus::Complained->advancesFrom(DeliveryStatus::Bounced))->toBeFalse();
});

it('flags bounces and spam complaints as failures', function () {
    expect(DeliveryStatus::Bounced->isFailure())->toBeTrue()
        ->and(DeliveryStatus::Complained->isFailure())->toBeTrue()
        ->and(DeliveryStatus::Delivered->isFailure())->toBeFalse();
});
