<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'status')) {
                $table->string('status')->default('pending')->after('order_number');
            }

            if (! Schema::hasColumn('orders', 'stripe_session_id')) {
                $table->string('stripe_session_id')->nullable()->after('status');
            }

            if (! Schema::hasColumn('orders', 'stripe_payment_intent')) {
                $table->string('stripe_payment_intent')->nullable()->after('stripe_session_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'stripe_payment_intent')) {
                $table->dropColumn('stripe_payment_intent');
            }

            if (Schema::hasColumn('orders', 'stripe_session_id')) {
                $table->dropColumn('stripe_session_id');
            }

            if (Schema::hasColumn('orders', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
