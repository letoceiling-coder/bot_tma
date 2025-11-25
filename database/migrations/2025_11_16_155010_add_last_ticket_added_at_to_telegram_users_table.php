<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Добавляет поле для отслеживания времени последнего добавления билета
     * Используется для автоматического добавления билетов каждые 24 часа
     */
    public function up(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->timestamp('last_ticket_added_at')->nullable()->after('last_active_at')->comment('Время последнего автоматического добавления билета (для расчета каждые 24 часа)');
            
            $table->index('last_ticket_added_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropIndex(['last_ticket_added_at']);
            $table->dropColumn('last_ticket_added_at');
        });
    }
};
