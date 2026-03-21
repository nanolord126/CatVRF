<?php

declare(strict_types=1);

namespace Database\Seeders;

/**
 * Бренды образования (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class EducationBrands extends BaseBrandSeeder
{
    public function run(): void
    {
        $this->seedBrands('Education', [
            ['name' => 'Harvard University', 'country' => 'USA'], ['name' => 'Stanford University', 'country' => 'USA'],
            ['name' => 'MIT', 'country' => 'USA'], ['name' => 'Oxford University', 'country' => 'UK'],
            ['name' => 'Cambridge University', 'country' => 'UK'], ['name' => 'Coursera', 'country' => 'USA'],
            ['name' => 'Udemy', 'country' => 'USA'], ['name' => 'edX', 'country' => 'USA'],
            ['name' => 'Khan Academy', 'country' => 'USA'], ['name' => 'Duolingo', 'country' => 'USA'],
            ['name' => 'Skillshare', 'country' => 'USA'], ['name' => 'MasterClass', 'country' => 'USA'],
            ['name' => 'LinkedIn Learning', 'country' => 'USA'], ['name' => 'Pluralsight', 'country' => 'USA'],
            ['name' => 'Pearson', 'country' => 'UK'], ['name' => 'McGraw-Hill', 'country' => 'USA'],
            ['name' => 'Cengage', 'country' => 'USA'], ['name' => 'Scholastic', 'country' => 'USA'],
            ['name' => 'Rosetta Stone', 'country' => 'USA'], ['name' => 'Babbel', 'country' => 'Germany'],
            ['name' => 'Chegg', 'country' => 'USA'], ['name' => 'Quizlet', 'country' => 'USA'],
            ['name' => 'Grammarly', 'country' => 'USA/Ukraine'], ['name' => 'Evernote', 'country' => 'USA'],
            ['name' => 'Notion', 'country' => 'USA'], ['name' => 'Instructure (Canvas)', 'country' => 'USA'],
            ['name' => 'Blackboard', 'country' => 'USA'], ['name' => 'Moodle', 'country' => 'Australia'],
            ['name' => 'Kahoot!', 'country' => 'Norway'], ['name' => 'EF Education First', 'country' => 'Switzerland'],
            ['name' => 'Berlitz', 'country' => 'USA'], ['name' => 'Kaplan', 'country' => 'USA'],
            ['name' => 'Prometric', 'country' => 'USA'], ['name' => 'ETS (TOEFL/GRE)', 'country' => 'USA'],
            ['name' => 'British Council', 'country' => 'UK'], ['name' => 'Goethe-Institut', 'country' => 'Germany'],
            ['name' => 'Alliance Française', 'country' => 'France'], ['name' => 'LEGO Education', 'country' => 'Denmark'],
            ['name' => 'National Geographic', 'country' => 'USA'], ['name' => 'Britannica', 'country' => 'USA'],
            ['name' => 'Kyoto University', 'country' => 'Japan'], ['name' => 'Tsinghua University', 'country' => 'China'],
            ['name' => 'ETH Zurich', 'country' => 'Switzerland'], ['name' => 'INSEAD', 'country' => 'France/Singapore'],
            ['name' => 'London School of Economics', 'country' => 'UK'], ['name' => 'Sorbonne', 'country' => 'France'],
            ['name' => 'Skillbox', 'country' => 'Russia'], ['name' => 'GeekBrains', 'country' => 'Russia'],
            ['name' => 'Yandex Practicum', 'country' => 'Russia'],
            ['name' => 'Kot-Edu (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
    }
}


