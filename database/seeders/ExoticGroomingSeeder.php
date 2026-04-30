<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\VetGrooming\Infrastructure\Models\ExoticSafetyProtocolModel;
use Modules\VetGrooming\Infrastructure\Models\ExoticTrainingCourseModel;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;
use Modules\VetGrooming\Domain\Enums\CertificationLevel;

final class ExoticGroomingSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSafetyProtocols();
        $this->seedTrainingCourses();
    }

    private function seedSafetyProtocols(): void
    {
        // Ferret Protocol
        ExoticSafetyProtocolModel::create([
            'tenant_id' => 1,
            'exotic_category' => 'small_mammals',
            'species' => 'ferret',
            'title' => 'Протокол груминга хорьков',
            'description' => 'Обязательный протокол безопасности для груминга хорьков',
            'is_active' => true,
            'checklist_items' => [
                ['item' => 'Проверка анальных желез', 'is_required' => true],
                ['item' => 'Оценка запаха и состояния кожи', 'is_required' => true],
                ['item' => 'Проверка на наличие паразитов', 'is_required' => true],
                ['item' => 'Оценка темперамента (агрессия 1-10)', 'is_required' => true],
                ['item' => 'Подготовка полотенца для фиксации', 'is_required' => true],
                ['item' => 'Проверка температуры помещения (20-22°C)', 'is_required' => true],
                ['item' => 'Подготовка перчаток', 'is_required' => true],
                ['item' => 'Наличие аптечки', 'is_required' => true],
            ],
            'temperature_requirements' => 'Температура помещения 20-22°C, избегать сквозняков',
            'handling_requirements' => 'Использовать полотенце для фиксации, работать в перчатках',
            'safety_precautions' => 'Хорьки кусаются сильно, всегда иметь аптечку под рукой',
            'prohibited_actions' => 'Запрещено поднимать за шкирку без опыта, использовать воду без необходимости',
            'specific_risks' => 'Сильные укусы, проблемы с анальными железами, стресс → агрессия',
            'requires_veterinary_notification' => true,
            'requires_owner_notification' => true,
            'post_procedure_recommendations' => 'Наблюдать за поведением 24 часа, проверить приём пищи',
        ]);

        // Rabbit Protocol
        ExoticSafetyProtocolModel::create([
            'tenant_id' => 1,
            'exotic_category' => 'small_mammals',
            'species' => 'rabbit',
            'title' => 'Протокол груминга кроликов',
            'description' => 'Обязательный протокол безопасности для груминга кроликов',
            'is_active' => true,
            'checklist_items' => [
                ['item' => 'Никогда не поднимать за уши!', 'is_required' => true],
                ['item' => 'Проверка зубов и когтей', 'is_required' => true],
                ['item' => 'Особая осторожность с длинношерстными', 'is_required' => true],
                ['item' => 'Профилактика стаза ЖКТ после стресса', 'is_required' => true],
                ['item' => 'Проверка температуры помещения', 'is_required' => true],
                ['item' => 'Подготовка фиксации', 'is_required' => true],
            ],
            'temperature_requirements' => 'Тёплое помещение без сквозняков',
            'handling_requirements' => 'Поддерживать под грудь и задние лапы, никогда не за уши',
            'safety_precautions' => 'Кролики хрупкие, риск переломов позвоночника',
            'prohibited_actions' => 'Категорически запрещено поднимать за уши!',
            'specific_risks' => 'Стаз ЖКТ, переломы позвоночника, стресс',
            'requires_veterinary_notification' => false,
            'requires_owner_notification' => true,
            'post_procedure_recommendations' => 'Обеспечить доступ к воде и сену, наблюдать за приёмом пищи',
        ]);

        // Chinchilla Protocol
        ExoticSafetyProtocolModel::create([
            'tenant_id' => 1,
            'exotic_category' => 'small_mammals',
            'species' => 'chinchilla',
            'title' => 'Протокол груминга шиншилл',
            'description' => 'Обязательный протокол безопасности для груминга шиншилл',
            'is_active' => true,
            'checklist_items' => [
                ['item' => 'Проверка состояния шерсти (колтуны)', 'is_required' => true],
                ['item' => 'Оценка хвоста (риск автотомии)', 'is_required' => true],
                ['item' => 'Проверка дыхания (респираторные проблемы)', 'is_required' => true],
                ['item' => 'Подготовка специального песка (никогда вода!)', 'is_required' => true],
                ['item' => 'Проверка глаз и ушей', 'is_required' => true],
                ['item' => 'Температура помещения (18-22°C)', 'is_required' => true],
                ['item' => 'Контроль пыли (респиратор)', 'is_required' => true],
                ['item' => 'Нежная фиксация хвоста', 'is_required' => true],
            ],
            'temperature_requirements' => 'Температура 18-22°C, низкая влажность',
            'handling_requirements' => 'Никакого давления на хвост, очень нежная фиксация',
            'safety_precautions' => 'Использовать респиратор при работе с песком',
            'prohibited_actions' => 'Категорически запрещено купать в воде!',
            'specific_risks' => 'Респираторные проблемы от пыли, хвостовая автотомия, стресс',
            'requires_veterinary_notification' => true,
            'requires_owner_notification' => true,
            'post_procedure_recommendations' => 'Наблюдать за дыханием, обеспечить спокойную обстановку',
        ]);

        // Large Parrot Protocol
        ExoticSafetyProtocolModel::create([
            'tenant_id' => 1,
            'exotic_category' => 'birds',
            'species' => 'large_parrots',
            'title' => 'Протокол груминга крупных попугаев',
            'description' => 'Обязательный протокол безопасности для груминга крупных попугаев',
            'is_active' => true,
            'checklist_items' => [
                ['item' => 'Оценка стресса до процедуры (1-10)', 'is_required' => true],
                ['item' => 'Подготовка полотенца/специального держателя', 'is_required' => true],
                ['item' => 'Проверка температуры помещения (24-26°C)', 'is_required' => true],
                ['item' => 'Защита глаз и дыхательных путей', 'is_required' => true],
                ['item' => 'Фотографирование маховых перьев до (если wing clip)', 'is_required' => true],
                ['item' => 'Проверка состояния клюва', 'is_required' => true],
                ['item' => 'Подготовка инструментов (стерильные)', 'is_required' => true],
                ['item' => 'Наличие ветеринара на подхвате', 'is_required' => true],
            ],
            'temperature_requirements' => 'Температура 24-26°C, без сквозняков',
            'handling_requirements' => 'Использовать полотенце для фиксации, защищать глаза',
            'safety_precautions' => 'Крупные попугаи могут нанести серьёзные травмы клювом',
            'prohibited_actions' => 'Не подрезать маховые перья без опыта, игнорировать стресс',
            'specific_risks' => 'Стресс → самотравмирование, повреждение маховых перьев, аспирация пыли',
            'requires_veterinary_notification' => true,
            'requires_owner_notification' => true,
            'post_procedure_recommendations' => 'Наблюдать за поведением и аппетитом, обеспечить тишину',
        ]);

        // Iguana Protocol
        ExoticSafetyProtocolModel::create([
            'tenant_id' => 1,
            'exotic_category' => 'reptiles',
            'species' => 'iguana',
            'title' => 'Протокол груминга игуан',
            'description' => 'Обязательный протокол безопасности для груминга игуан',
            'is_active' => true,
            'checklist_items' => [
                ['item' => 'Оценка темперамента (агрессия 1-10)', 'is_required' => true],
                ['item' => 'Проверка температуры тела (норма 30-35°C)', 'is_required' => true],
                ['item' => 'Подготовка термометра и нагревательного коврика', 'is_required' => true],
                ['item' => 'Температура помещения (26-30°C)', 'is_required' => true],
                ['item' => 'Влажность (60-80%)', 'is_required' => true],
                ['item' => 'Проверка чешуи на повреждения', 'is_required' => true],
                ['item' => 'Подготовка перчаток', 'is_required' => true],
                ['item' => 'Наличие ветеринара (для крупных)', 'is_required' => true],
            ],
            'temperature_requirements' => 'Температура помещения 26-30°C, влажность 60-80%',
            'handling_requirements' => 'Работать в перчатках, поддерживать температурный режим',
            'safety_precautions' => 'Игуаны имеют мощные укусы и хвост как оружие',
            'prohibited_actions' => 'Работать без перчаток, дергать за хвост',
            'specific_risks' => 'Мощные укусы, хвостовая автотомия, температурный стресс',
            'requires_veterinary_notification' => true,
            'requires_owner_notification' => true,
            'post_procedure_recommendations' => 'Обеспечить правильный температурный режим и влажность',
        ]);

        // Giant Dog Protocol
        ExoticSafetyProtocolModel::create([
            'tenant_id' => 1,
            'exotic_category' => 'large_mammals',
            'species' => 'giant_dog',
            'title' => 'Протокол груминга гигантских пород',
            'description' => 'Обязательный протокол безопасности для груминга гигантских пород',
            'is_active' => true,
            'checklist_items' => [
                ['item' => 'Оценка агрессии (1-10)', 'is_required' => true],
                ['item' => 'Обязательный намордник или Gentle Leader', 'is_required' => true],
                ['item' => 'Двухгрумерная фиксация (агрессия ≥6)', 'is_required' => true],
                ['item' => 'Проверка суставов и лап перед стрижкой когтей', 'is_required' => true],
                ['item' => 'Контроль температуры (крупные собаки перегреваются)', 'is_required' => true],
                ['item' => 'Фотографирование состояния шерсти до', 'is_required' => true],
                ['item' => 'Наличие второго грумера (агрессия ≥6)', 'is_required' => true],
                ['item' => 'Наличие ветеринара (агрессия ≥8)', 'is_required' => true],
            ],
            'temperature_requirements' => 'Контроль перегрева, хорошее проветривание',
            'handling_requirements' => 'Обязательный намордник, двухгрумерная фиксация при агрессии',
            'safety_precautions' => 'Гигантские породы могут серьёзно травмировать даже случайно',
            'prohibited_actions' => 'Работать в одиночку с агрессией ≥6, без намордника',
            'specific_risks' => 'Физическая сила, серьёзные укусы, перегрев',
            'requires_veterinary_notification' => true,
            'requires_owner_notification' => true,
            'post_procedure_recommendations' => 'Наблюдать за состоянием, обеспечить отдых и воду',
        ]);
    }

    private function seedTrainingCourses(): void
    {
        // Certified Level Courses
        ExoticTrainingCourseModel::create([
            'tenant_id' => 1,
            'title' => 'Базовый курс по экзотическим животным',
            'description' => 'Введение в груминг экзотических животных, основы анатомии и поведения',
            'exotic_category' => 'small_mammals',
            'exotic_group' => 'group_c',
            'certification_level' => 'certified',
            'duration_hours' => 10,
            'passing_score' => 80,
            'is_mandatory' => true,
            'is_active' => true,
            'curriculum' => 'Анатомия мелких млекопитающих, поведение, базовые техники груминга, безопасность',
        ]);

        ExoticTrainingCourseModel::create([
            'tenant_id' => 1,
            'title' => 'Груминг мелких птиц',
            'description' => 'Базовый груминг волнистых попугайчиков и других мелких птиц',
            'exotic_category' => 'birds',
            'exotic_group' => 'group_c',
            'certification_level' => 'certified',
            'duration_hours' => 8,
            'passing_score' => 80,
            'is_mandatory' => true,
            'is_active' => true,
            'curriculum' => 'Анатомия птиц, стресс-менеджмент, стрижка когтей, уход за перьями',
        ]);

        // Advanced Level Courses
        ExoticTrainingCourseModel::create([
            'tenant_id' => 1,
            'title' => 'Продвинутый груминг хорьков',
            'description' => 'Работа с колтунами, анальные железы, сложные случаи',
            'exotic_category' => 'small_mammals',
            'exotic_group' => 'group_a',
            'certification_level' => 'advanced',
            'duration_hours' => 15,
            'passing_score' => 85,
            'is_mandatory' => false,
            'is_active' => true,
            'curriculum' => 'Работа с колтунами, анальные железы, агрессивные хорьки, экстренные ситуации',
        ]);

        ExoticTrainingCourseModel::create([
            'tenant_id' => 1,
            'title' => 'Груминг шиншилл и дегу',
            'description' => 'Специфические требования к грумингу шиншилл и дегу',
            'exotic_category' => 'small_mammals',
            'exotic_group' => 'group_a',
            'certification_level' => 'advanced',
            'duration_hours' => 12,
            'passing_score' => 85,
            'is_mandatory' => false,
            'is_active' => true,
            'curriculum' => 'Песочные ванны, уход за шерстью, респираторные проблемы, хвостовая автотомия',
        ]);

        ExoticTrainingCourseModel::create([
            'tenant_id' => 1,
            'title' => 'Груминг средних попугаев',
            'description' => 'Работа с кореллами, розеллами и другими средними попугаями',
            'exotic_category' => 'birds',
            'exotic_group' => 'group_b',
            'certification_level' => 'advanced',
            'duration_hours' => 12,
            'passing_score' => 85,
            'is_mandatory' => false,
            'is_active' => true,
            'curriculum' => 'Подрезка крыльев, уход за клювом, стресс-менеджмент, фиксация',
        ]);

        // Master Level Courses
        ExoticTrainingCourseModel::create([
            'tenant_id' => 1,
            'title' => 'Экспертный груминг крупных попугаев',
            'description' => 'Работа с ара, какаду, жако и другими крупными попугаями',
            'exotic_category' => 'birds',
            'exotic_group' => 'group_a',
            'certification_level' => 'master',
            'duration_hours' => 20,
            'passing_score' => 90,
            'is_mandatory' => false,
            'is_active' => true,
            'curriculum' => 'Сложные случаи, агрессивные птицы, экстренные ситуации, обучение других',
        ]);

        ExoticTrainingCourseModel::create([
            'tenant_id' => 1,
            'title' => 'Груминг рептилий высокого уровня',
            'description' => 'Работа с игуанами, крупными ящерицами и змеями',
            'exotic_category' => 'reptiles',
            'exotic_group' => 'group_a',
            'certification_level' => 'master',
            'duration_hours' => 25,
            'passing_score' => 90,
            'is_mandatory' => false,
            'is_active' => true,
            'curriculum' => 'Температурный контроль, фиксация, специфические заболевания, экстренные случаи',
        ]);

        ExoticTrainingCourseModel::create([
            'tenant_id' => 1,
            'title' => 'Груминг гигантских пород собак',
            'description' => 'Работа с мастифами, сенбернарами и другими гигантскими породами',
            'exotic_category' => 'large_mammals',
            'exotic_group' => 'group_a',
            'certification_level' => 'master',
            'duration_hours' => 20,
            'passing_score' => 90,
            'is_mandatory' => false,
            'is_active' => true,
            'curriculum' => 'Работа с агрессией, двухгрумерная фиксация, экстренные ситуации, шоу-груминг',
        ]);
    }
}
