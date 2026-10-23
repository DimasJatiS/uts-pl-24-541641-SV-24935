<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('coin_id', 64);
            $table->foreign('coin_id')->references('id')->on('coins')->restrictOnDelete();
            // DECIMAL, bukan FLOAT: presisi uang dan jumlah kripto harus pasti.
            $table->decimal('amount', 30, 10);
            $table->decimal('avg_buy_price_usd', 24, 8);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            // Satu koin hanya boleh muncul sekali per user.
            $table->unique(['user_id', 'coin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holdings');
    }
};
