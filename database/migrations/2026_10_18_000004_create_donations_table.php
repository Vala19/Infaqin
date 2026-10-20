<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('donor_name');
            $table->string('email');
            $table->string('payment_method', 30);
            $table->boolean('is_anonymous')->default(false);
            $table->string('on_behalf_of', 100)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donation_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->unsignedInteger('amens_count')->default(0);
            $table->timestamps();
        });

        Schema::create('comment_amens', function (Blueprint $table) {
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['comment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_amens');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('donations');
    }
};
