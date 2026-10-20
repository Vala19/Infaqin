<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaigner_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 40)->index();
            $table->string('image')->nullable();
            $table->string('summary', 300);
            $table->text('description');
            $table->string('location')->nullable();
            $table->unsignedBigInteger('target_amount');
            $table->unsignedBigInteger('collected_amount')->default(0);
            $table->unsignedInteger('donor_count')->default(0);
            $table->date('deadline')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('description');
            $table->timestamp('withdrawn_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('campaigns');
    }
};
