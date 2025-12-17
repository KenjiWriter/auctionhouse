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
        // For SQLite, no schema change is needed as it treats ENUMs as VARCHARs without strict constraints by default.
        // For MySQL, we need to explicitly update the enum definition.
        if (config('database.default') === 'mysql') {
             \Illuminate\Support\Facades\DB::statement("ALTER TABLE auctions MODIFY COLUMN status ENUM('draft', 'upcoming', 'active', 'ended', 'ended_without_sale', 'cancelled') DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         if (config('database.default') === 'mysql') {
             \Illuminate\Support\Facades\DB::statement("ALTER TABLE auctions MODIFY COLUMN status ENUM('draft', 'active', 'ended', 'cancelled') DEFAULT 'draft'");
        }
    }
};
