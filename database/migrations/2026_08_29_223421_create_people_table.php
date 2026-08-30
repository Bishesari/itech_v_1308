<?php

use App\Enums\NationalityType;
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
        Schema::create('people', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('nationality_type')
                ->default(NationalityType::Iranian->value);

            $table->string('identity', 20)->unique();

            $table->boolean('gender')->nullable();
            $table->string('f_name_fa', 30)->nullable();
            $table->string('l_name_fa', 40)->nullable();
            $table->string('nickname', 30)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
