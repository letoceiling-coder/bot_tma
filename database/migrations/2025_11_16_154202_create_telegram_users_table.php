<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Создает таблицу для хранения пользователей Telegram Mini App
     * с данными из Telegram и дополнительными полями для игры
     */
    public function up(): void
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            
            // Уникальный ID пользователя из Telegram
            $table->unsignedBigInteger('telegram_id')->unique();
            
            // Основные данные из Telegram
            $table->string('username', 255)->nullable();
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('language_code', 10)->nullable()->default('ru');
            $table->string('photo_url', 500)->nullable();
            
            // Дополнительные поля для игры
            $table->unsignedInteger('tickets')->default(0)->comment('Количество билетов для крутки колеса');
            $table->unsignedInteger('referrals_count')->default(0)->comment('Количество рефералов - сколько пользователей пришло по ссылке этого пользователя');
            
            // Связь с пользователем, который пригласил (реферальная система)
            $table->unsignedBigInteger('invited_by_telegram_user_id')->nullable();
            
            // Метаданные
            $table->timestamp('last_active_at')->nullable()->comment('Последняя активность пользователя');
            $table->json('metadata')->nullable()->comment('Дополнительные данные в формате JSON');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы для оптимизации запросов
            $table->index('telegram_id');
            $table->index('username');
            $table->index('invited_by_telegram_user_id');
            $table->index('referrals_count');
            $table->index('tickets');
            $table->index('last_active_at');
            $table->index('created_at');
        });
        
        // Foreign key добавляем после создания таблицы
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->foreign('invited_by_telegram_user_id')
                ->references('telegram_id')
                ->on('telegram_users')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropForeign(['invited_by_telegram_user_id']);
        });
        
        Schema::dropIfExists('telegram_users');
    }
};
