<?php

namespace Database\Seeders;

use App\Models\Channel;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channels = [
            [
                'title' => 'Основной канал',
                'description' => 'Новости и обновления проекта',
                'username' => 'your_channel',
                'url' => 'https://t.me/your_channel',
                'avatar' => null,
                'telegram_chat_id' => null,
                'sort_order' => 1,
                'is_active' => true,
                'is_required' => true,
            ],
            [
                'title' => 'Новости проекта',
                'description' => 'Актуальная информация о проекте',
                'username' => 'your_news',
                'url' => 'https://t.me/your_news',
                'avatar' => null,
                'telegram_chat_id' => null,
                'sort_order' => 2,
                'is_active' => true,
                'is_required' => true,
            ],
            [
                'title' => 'Поддержка',
                'description' => 'Помощь и ответы на вопросы',
                'username' => 'your_support',
                'url' => 'https://t.me/your_support',
                'avatar' => null,
                'telegram_chat_id' => null,
                'sort_order' => 3,
                'is_active' => true,
                'is_required' => true,
            ],
        ];

        foreach ($channels as $channelData) {
            Channel::updateOrCreate(
                ['username' => $channelData['username']],
                $channelData
            );
        }
    }
}

