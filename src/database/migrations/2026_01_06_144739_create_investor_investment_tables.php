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
        Schema::create('investors', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('investor_id')->unique(); // unique investor ID, auto-indexed
            $table->string('name');
            $table->unsignedInteger('age');
            $table->timestamps();
        });

        Schema::create('investments', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('owner_id');
            $table->foreign('owner_id')
                ->references('investor_id')
                ->on('investors')
                ->cascadeOnDelete();

            $table->decimal('investment_amount', 15, 2);
            $table->date('investment_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
        Schema::dropIfExists('investors');
    }
};
