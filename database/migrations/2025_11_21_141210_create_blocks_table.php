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
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('block_id')->unique()->comment('Уникальный идентификатор блока (например: index-1, index-5)');
            $table->enum('type', ['command', 'text', 'menu', 'input', 'confirmation'])->comment('Тип блока');
            $table->text('text')->nullable()->comment('Текст блока');
            $table->text('value')->nullable()->comment('Значение блока (для command типа)');
            $table->json('buttons')->nullable()->comment('Кнопки для menu типа');
            $table->string('target')->nullable()->comment('ID следующего блока (старый формат)');
            $table->string('next_block')->nullable()->comment('ID следующего блока после выполнения');
            $table->string('confirmation_block')->nullable()->comment('ID блока подтверждения (для input типа)');
            $table->string('command')->nullable()->comment('Команда для command типа (например: /start)');
            $table->string('input_type')->nullable()->comment('Тип ввода для input блока (text, phone, email)');
            $table->string('confirm_button')->nullable()->comment('Текст кнопки подтверждения');
            $table->string('cancel_button')->nullable()->comment('Текст кнопки отмены');
            $table->string('confirm_action')->nullable()->comment('Действие для кнопки подтверждения');
            $table->string('cancel_action')->nullable()->comment('Действие для кнопки отмены');
            $table->json('metadata')->nullable()->comment('Дополнительные данные в формате JSON');
            $table->unsignedInteger('sort_order')->default(0)->comment('Порядок сортировки');
            $table->boolean('is_active')->default(true)->comment('Активен ли блок');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('block_id');
            $table->index('type');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
