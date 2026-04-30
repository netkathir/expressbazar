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

        DB::table('notification_templates')->updateOrInsert(
            [
                'notification_type' => 'ORDER_STATUS_UPDATE',
                'channel' => 'email',
            ],
            [
                'template_name' => 'Order Status Update',
                'subject' => 'Order #{{order_number}} is now {{status}}',
                'message_body' => "Hello {{name}},\n\nYour order #{{order_number}} status has been updated to {{status}}.\n\nVendor: {{vendor_name}}\nTotal: {{amount}}\n\nThank you for shopping with us.",
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            return;
        }

        DB::table('notification_templates')
            ->where('notification_type', 'ORDER_STATUS_UPDATE')
            ->where('channel', 'email')
            ->delete();
    }
};
