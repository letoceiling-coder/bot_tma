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
        Schema::create('wheel_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('required')->default(false)->comment('Если true - используется вероятностная логика, если false - остановка на секторе с text="0"');
            $table->timestamps();
        });

        // Создаем единственную запись с настройками по умолчанию
        \DB::table('wheel_settings')->insert([
            'required' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wheel_settings');
    }
};
