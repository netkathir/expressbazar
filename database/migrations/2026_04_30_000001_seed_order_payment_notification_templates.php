<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            return;
        }

        foreach ($this->templates() as $template) {
            DB::table('notification_templates')->updateOrInsert(
                [
                    'notification_type' => $template['notification_type'],
                    'channel' => 'email',
                ],
                array_merge($template, [
                    'channel' => 'email',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            return;
        }

        DB::table('notification_templates')
            ->whereIn('notification_type', array_column($this->templates(), 'notification_type'))
            ->where('channel', 'email')
            ->delete();
    }

    private function templates(): array
    {
        return [
            [
                'template_name' => 'Order Confirmed',
                'notification_type' => 'ORDER_CONFIRMED',
                'subject' => 'Your Order #{{order_id}} is Confirmed',
                'message_body' => "Hello {{name}},\n\nYour order #{{order_id}} has been successfully placed.\n\nTotal: {{amount}}\nItems: {{items}}\n\nThank you for shopping with us.",
            ],
            [
                'template_name' => 'Order Received Vendor',
                'notification_type' => 'ORDER_RECEIVED_VENDOR',
                'subject' => 'New Order Received #{{order_id}}',
                'message_body' => "New order received. Order ID: #{{order_id}}.\nCustomer: {{customer_name}}\nTotal: {{amount}}",
            ],
            [
                'template_name' => 'Order Delivered',
                'notification_type' => 'ORDER_DELIVERED',
                'subject' => 'Your Order #{{order_id}} Has Been Delivered',
                'message_body' => 'Your order #{{order_id}} has been delivered successfully.',
            ],
            [
                'template_name' => 'Payment Success',
                'notification_type' => 'PAYMENT_SUCCESS',
                'subject' => 'Payment Successful for Order #{{order_id}}',
                'message_body' => "Payment successful for Order #{{order_id}}.\nAmount: {{amount}}",
            ],
        ];
    }
};
