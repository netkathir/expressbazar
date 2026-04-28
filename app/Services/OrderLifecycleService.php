<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderLog;
use Illuminate\Validation\ValidationException;

class OrderLifecycleService
{
    private const TRANSITIONS = [
        'pending' => ['accepted', 'cancelled'],
        'accepted' => ['processing', 'cancelled'],
        'processing' => ['dispatched', 'cancelled'],
        'dispatched' => ['delivered'],
        'delivered' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function canTransition(?string $from, string $to): bool
    {
        $from = mb_strtolower((string) ($from ?: 'pending'));
        $to = mb_strtolower($to);

        if ($from === $to) {
            return true;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function transition(Order $order, string $to, ?int $changedBy = null, ?string $note = null): void
    {
        $from = mb_strtolower((string) $order->order_status);
        $to = mb_strtolower($to);

        if (! $this->canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'order_status' => 'Invalid order status transition from '.ucfirst($from).' to '.ucfirst($to).'.',
            ]);
        }

        if ($from === $to) {
            return;
        }

        $updates = [
            'order_status' => $to,
            'updated_by' => $changedBy,
        ];

        if (array_key_exists('status', $order->getAttributes())) {
            $updates['status'] = $to;
        }

        $order->update($updates);
        $this->log($order, $from, $to, $changedBy, $note);
    }

    public function log(Order $order, ?string $from, string $to, ?int $changedBy = null, ?string $note = null): void
    {
        OrderLog::create([
            'order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $changedBy,
            'note' => $note,
        ]);
    }
}
