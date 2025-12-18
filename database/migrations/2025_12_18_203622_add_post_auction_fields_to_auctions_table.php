<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->string('post_status')->default('none')->after('status');
            $table->timestamp('seller_contacted_at')->nullable()->after('winner_id');
            $table->timestamp('buyer_confirmed_at')->nullable()->after('seller_contacted_at');
            $table->timestamp('seller_confirmed_at')->nullable()->after('buyer_confirmed_at');
            $table->timestamp('cancelled_at')->nullable()->after('seller_confirmed_at');
            $table->text('cancel_reason')->nullable()->after('cancelled_at');
            $table->timestamp('disputed_at')->nullable()->after('cancel_reason');
            $table->text('dispute_reason')->nullable()->after('disputed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn([
                'post_status',
                'seller_contacted_at',
                'buyer_confirmed_at',
                'seller_confirmed_at',
                'cancelled_at',
                'cancel_reason',
                'disputed_at',
                'dispute_reason',
            ]);
        });
    }
};
