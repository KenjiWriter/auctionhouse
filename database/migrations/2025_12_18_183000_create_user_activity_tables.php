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
        Schema::create('user_activity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained()->onDelete('cascade');
            $table->string('type')->index(); // auction_view, category_view, search, bid, watch
            $table->foreignId('auction_id')->nullable()->index()->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->index()->constrained()->onDelete('cascade');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('user_category_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->index()->constrained()->onDelete('cascade');
            $table->integer('score')->default(0);
            $table->timestamp('last_interaction_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_category_preferences');
        Schema::dropIfExists('user_activity_events');
    }
};
