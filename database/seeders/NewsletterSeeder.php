<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Newsletter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Информационные рассылки (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class NewsletterSeeder extends Seeder
{
    public function run(): void
    {
        $newsletters = [             [                 'subject' => 'Еженедельные обновления компании',                 'sender_email' => 'newsletter@company.local',                 'content' => 'Дорогие сотрудники! Это наше еженедельное письмо с обновлениями. На этой неделе мы достигли отличных результатов в продажах и улучшили процессы в логистике.',                 'recipient_count' => 150,                 'scheduled_at' => now()->addDays(7),                 'sent_at' => null,                 'status' => 'scheduled',             ],             [                 'subject' => 'Приглашение на корпоративное событие',                 'sender_email' => 'hr@company.local',                 'content' => 'Приглашаем вас принять участие в нашем ежегодном корпоративном событии. Дата: 15 февраля 2024. Место: конференц-зал на 3-м этаже.',                 'recipient_count' => 200,                 'scheduled_at' => now()->addDays(14),                 'sent_at' => null,                 'status' => 'draft',             ],             [                 'subject' => 'Обновление системы IT',                 'sender_email' => 'it@company.local',                 'content' => 'Уведомляем о плановом обновлении системы в выходные. Процедура обновления займет 4 часа с 22:00 до 02:00.',                 'recipient_count' => 200,                 'scheduled_at' => now()->subWeek(),                 'sent_at' => now()->subWeek()->addHours(1),                 'status' => 'sent',             ],             [                 'subject' => 'Результаты опроса сотрудников',                 'sender_email' => 'hr@company.local',                 'content' => 'Спасибо всем, кто принял участие в опросе. Результаты показывают высокую степень удовлетворенности работой компании.',                 'recipient_count' => 180,                 'scheduled_at' => now()->subDays(5),                 'sent_at' => now()->subDays(5)->addMinutes(30),                 'status' => 'sent',             ],             [                 'subject' => 'Объявление о вакансиях',                 'sender_email' => 'hr@company.local',                 'content' => 'Компания открыла новые позиции: Senior Developer, Project Manager, QA Engineer. Приглашаем заинтересованных кандидатов отправить резюме.',                 'recipient_count' => 0,                 'scheduled_at' => null,                 'sent_at' => null,                 'status' => 'draft',             ],         ];          foreach ($newsletters as $newsletter) {             Newsletter::create(array_merge($newsletter, [                 'correlation_id' => Str::uuid()->toString(),             ]));         }     } }