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
        Schema::create('verification_challenges', function (Blueprint $table) {
            $table->id();

            $table->string('first_name_fa', 30);
            $table->string('last_name_fa', 40);

            $table->unsignedTinyInteger('nationality_type');
            $table->string('identity', 20);
            $table->string('mobile', 20);

            $table->string('purpose', 50);

            $table->string('verification_code', 10);

            $table->text('fingerprint')->nullable();
            $table->string('ip', 45)->nullable();

            $table->unsignedTinyInteger('attempts')->default(0);

            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            $table->index(['purpose', 'mobile']);
            $table->index('expires_at');
            $table->index('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_challenges');
    }
};
