<?php

namespace Database\Seeders;

use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\VerticalCourse;
use Illuminate\Database\Seeder;

class B2BVerticalCoursesSeeder extends Seeder
{
    public function run(): void
    {
        // Создаем курсы для Beauty вертикали
        $this->seedBeautyCourses();
        
        // Создаем курсы для Hotels вертикали
        $this->seedHotelsCourses();
        
        // Создаем курсы для Flowers вертикали
        $this->seedFlowersCourses();
        
        // Создаем курсы для Auto вертикали
        $this->seedAutoCourses();
        
        // Создаем курсы для Medical вертикали
        $this->seedMedicalCourses();
        
        // Создаем курсы для Fitness вертикали
        $this->seedFitnessCourses();
        
        // Создаем курсы для Restaurants вертикали
        $this->seedRestaurantsCourses();
        
        // Создаем курсы для Pharmacy вертикали
        $this->seedPharmacyCourses();
    }

    private function seedBeautyCourses(): void
    {
        $courses = [
            [
                'title' => 'Основы работы в салоне красоты',
                'description' => 'Базовый курс для новых сотрудников салона красоты',
                'vertical' => 'beauty',
                'target_role' => 'receptionist',
                'difficulty_level' => 'beginner',
                'duration_hours' => 10,
                'is_required' => true,
            ],
            [
                'title' => 'Мастерство парикмахера: начальный уровень',
                'description' => 'Основные техники стрижки и укладки',
                'vertical' => 'beauty',
                'target_role' => 'master',
                'difficulty_level' => 'beginner',
                'duration_hours' => 40,
                'is_required' => true,
            ],
            [
                'title' => 'Мастерство парикмахера: продвинутый уровень',
                'description' => 'Сложные техники стрижки и окрашивания',
                'vertical' => 'beauty',
                'target_role' => 'master',
                'difficulty_level' => 'advanced',
                'duration_hours' => 60,
                'is_required' => false,
            ],
            [
                'title' => 'Управление салоном красоты',
                'description' => 'Курс для администраторов и менеджеров салонов',
                'vertical' => 'beauty',
                'target_role' => 'manager',
                'difficulty_level' => 'intermediate',
                'duration_hours' => 30,
                'is_required' => true,
            ],
            [
                'title' => 'Клиентский сервис в бьюти-индустрии',
                'description' => 'Работа с клиентами, обработка жалоб, повышение лояльности',
                'vertical' => 'beauty',
                'target_role' => null,
                'difficulty_level' => 'beginner',
                'duration_hours' => 15,
                'is_required' => true,
            ],
        ];

        $this->createVerticalCourses($courses);
    }

    private function seedHotelsCourses(): void
    {
        $courses = [
            [
                'title' => 'Основы гостеприимства',
                'description' => 'Базовый курс для персонала отеля',
                'vertical' => 'hotels',
                'target_role' => 'receptionist',
                'difficulty_level' => 'beginner',
                'duration_hours' => 20,
                'is_required' => true,
            ],
            [
                'title' => 'Работа на ресепшене',
                'description' => 'Регистрация гостей, бронирование, решение проблем',
                'vertical' => 'hotels',
                'target_role' => 'receptionist',
                'difficulty_level' => 'intermediate',
                'duration_hours' => 25,
                'is_required' => true,
            ],
            [
                'title' => 'Уборка номеров: стандарты качества',
                'description' => 'Профессиональная уборка гостиничных номеров',
                'vertical' => 'hotels',
                'target_role' => 'housekeeper',
                'difficulty_level' => 'beginner',
                'duration_hours' => 15,
                'is_required' => true,
            ],
            [
                'title' => 'Управление отелем',
                'description' => 'Курс для менеджеров отелей',
                'vertical' => 'hotels',
                'target_role' => 'manager',
                'difficulty_level' => 'advanced',
                'duration_hours' => 50,
                'is_required' => true,
            ],
            [
                'title' => 'Консьерж-сервис',
                'description' => 'Организация досуга гостей, трансфер, экскурсии',
                'vertical' => 'hotels',
                'target_role' => 'concierge',
                'difficulty_level' => 'intermediate',
                'duration_hours' => 20,
                'is_required' => false,
            ],
        ];

        $this->createVerticalCourses($courses);
    }

    private function seedFlowersCourses(): void
    {
        $courses = [
            [
                'title' => 'Основы флористики',
                'description' => 'Базовый курс для начинающих флористов',
                'vertical' => 'flowers',
                'target_role' => 'florist',
                'difficulty_level' => 'beginner',
                'duration_hours' => 30,
                'is_required' => true,
            ],
            [
                'title' => 'Свадебная флористика',
                'description' => 'Создание букетов для невесты и декорации зала',
                'vertical' => 'flowers',
                'target_role' => 'florist',
                'difficulty_level' => 'advanced',
                'duration_hours' => 40,
                'is_required' => false,
            ],
            [
                'title' => 'Композиции из цветов',
                'description' => 'Создание цветочных композиций для разных случаев',
                'vertical' => 'flowers',
                'target_role' => 'florist',
                'difficulty_level' => 'intermediate',
                'duration_hours' => 35,
                'is_required' => false,
            ],
            [
                'title' => 'Управление цветочным магазином',
                'description' => 'Курс для менеджеров цветочных магазинов',
                'vertical' => 'flowers',
                'target_role' => 'manager',
                'difficulty_level' => 'intermediate',
                'duration_hours' => 25,
                'is_required' => true,
            ],
            [
                'title' => 'Доставка цветов',
                'description' => 'Стандарты доставки и обслуживания клиентов',
                'vertical' => 'flowers',
                'target_role' => 'delivery',
                'difficulty_level' => 'beginner',
                'duration_hours' => 10,
                'is_required' => true,
            ],
        ];

        $this->createVerticalCourses($courses);
    }

    private function seedAutoCourses(): void
    {
        $courses = [
            [
                'title' => 'Основы автосервиса',
                'description' => 'Базовый курс для сотрудников автосервиса',
                'vertical' => 'auto',
                'target_role' => 'advisor',
                'difficulty_level' => 'beginner',
                'duration_hours' => 20,
                'is_required' => true,
            ],
            [
                'title' => 'Диагностика автомобилей',
                'description' => 'Современные методы диагностики',
                'vertical' => 'auto',
                'target_role' => 'mechanic',
                'difficulty_level' => 'intermediate',
                'duration_hours' => 40,
                'is_required' => true,
            ],
            [
                'title' => 'Ремонт двигателя',
                'description' => 'Технический ремонт двигателей',
                'vertical' => 'auto',
                'target_role' => 'mechanic',
                'difficulty_level' => 'advanced',
                'duration_hours' => 60,
                'is_required' => false,
            ],
            [
                'title' => 'Управление автосервисом',
                'description' => 'Курс для менеджеров автосервисов',
                'vertical' => 'auto',
                'target_role' => 'manager',
                'difficulty_level' => 'advanced',
                'duration_hours' => 45,
                'is_required' => true,
            ],
            [
                'title' => 'Клиентский сервис в автосервисе',
                'description' => 'Работа с клиентами, объяснение ремонта, ценообразование',
                'vertical' => 'auto',
                'target_role' => 'advisor',
                'difficulty_level' => 'intermediate',
                'duration_hours' => 15,
                'is_required' => true,
            ],
        ];

        $this->createVerticalCourses($courses);
    }

    private function seedMedicalCourses(): void
    {
        $courses = [
            [
                'title' => 'Основы работы в медицинской клинике',
                'description' => 'Базовый курс для персонала клиники',
                'vertical' => 'medical',
                'target_role' => 'receptionist',
                'difficulty_level' => 'beginner',
                'duration_hours' => 15,
                'is_required' => true,
            ],
            [
                'title' => 'Медицинский этикет',
                'description' => 'Этические нормы в медицине',
                'vertical' => 'medical',
                'target_role' => null,
                'difficulty_level' => 'beginner',
                'duration_hours' => 10,
                'is_required' => true,
            ],
            [
                'title' => 'Управление медицинской клиникой',
                'description' => 'Курс для администраторов клиник',
                'vertical' => 'medical',
                'target_role' => 'administrator',
                'difficulty_level' => 'advanced',
                'duration_hours' => 40,
                'is_required' => true,
            ],
            [
                'title' => 'Работа с пациентами',
                'description' => 'Коммуникация с пациентами, конфиденциальность',
                'vertical' => 'medical',
                'target_role' => 'nurse',
                'difficulty_level' => 'beginner',
                'duration_hours' => 20,
                'is_required' => true,
            ],
        ];

        $this->createVerticalCourses($courses);
    }

    private function seedFitnessCourses(): void
    {
        $courses = [
            [
                'title' => 'Основы работы в фитнес-клубе',
                'description' => 'Базовый курс для персонала фитнес-клуба',
                'vertical' => 'fitness',
                'target_role' => 'receptionist',
                'difficulty_level' => 'beginner',
                'duration_hours' => 10,
                'is_required' => true,
            ],
            [
                'title' => 'Персональный тренер: основы',
                'description' => 'Базовые методики тренировок',
                'vertical' => 'fitness',
                'target_role' => 'trainer',
                'difficulty_level' => 'beginner',
                'duration_hours' => 40,
                'is_required' => true,
            ],
            [
                'title' => 'Персональный тренер: продвинутый уровень',
                'description' => 'Специализированные методики тренировок',
                'vertical' => 'fitness',
                'target_role' => 'trainer',
                'difficulty_level' => 'advanced',
                'duration_hours' => 60,
                'is_required' => false,
            ],
            [
                'title' => 'Управление фитнес-клубом',
                'description' => 'Курс для менеджеров фитнес-клубов',
                'vertical' => 'fitness',
                'target_role' => 'manager',
                'difficulty_level' => 'intermediate',
                'duration_hours' => 35,
                'is_required' => true,
            ],
        ];

        $this->createVerticalCourses($courses);
    }

    private function seedRestaurantsCourses(): void
    {
        $courses = [
            [
                'title' => 'Основы работы в ресторане',
                'description' => 'Базовый курс для персонала ресторана',
                'vertical' => 'restaurants',
                'target_role' => 'waiter',
                'difficulty_level' => 'beginner',
                'duration_hours' => 15,
                'is_required' => true,
            ],
            [
                'title' => 'Сервис официантов',
                'description' => 'Профессиональный сервис в ресторане',
                'vertical' => 'restaurants',
                'target_role' => 'waiter',
                'difficulty_level' => 'intermediate',
                'duration_hours' => 25,
                'is_required' => true,
            ],
            [
                'title' => 'Кулинария: основы',
                'description' => 'Базовые кулинарные навыки',
                'vertical' => 'restaurants',
                'target_role' => 'chef',
                'difficulty_level' => 'beginner',
                'duration_hours' => 40,
                'is_required' => true,
            ],
            [
                'title' => 'Управление рестораном',
                'description' => 'Курс для менеджеров ресторанов',
                'vertical' => 'restaurants',
                'target_role' => 'manager',
                'difficulty_level' => 'advanced',
                'duration_hours' => 50,
                'is_required' => true,
            ],
        ];

        $this->createVerticalCourses($courses);
    }

    private function seedPharmacyCourses(): void
    {
        $courses = [
            [
                'title' => 'Основы работы в аптеке',
                'description' => 'Базовый курс для персонала аптеки',
                'vertical' => 'pharmacy',
                'target_role' => 'assistant',
                'difficulty_level' => 'beginner',
                'duration_hours' => 20,
                'is_required' => true,
            ],
            [
                'title' => 'Провизор: профессиональные навыки',
                'description' => 'Профессиональные навыки провизора',
                'vertical' => 'pharmacy',
                'target_role' => 'pharmacist',
                'difficulty_level' => 'intermediate',
                'duration_hours' => 45,
                'is_required' => true,
            ],
            [
                'title' => 'Управление аптекой',
                'description' => 'Курс для менеджеров аптек',
                'vertical' => 'pharmacy',
                'target_role' => 'manager',
                'difficulty_level' => 'advanced',
                'duration_hours' => 40,
                'is_required' => true,
            ],
            [
                'title' => 'Консультирование клиентов',
                'description' => 'Консультирование по лекарственным средствам',
                'vertical' => 'pharmacy',
                'target_role' => 'pharmacist',
                'difficulty_level' => 'intermediate',
                'duration_hours' => 30,
                'is_required' => true,
            ],
        ];

        $this->createVerticalCourses($courses);
    }

    private function createVerticalCourses(array $courses): void
    {
        foreach ($courses as $courseData) {
            $course = Course::firstOrCreate(
                [
                    'title' => $courseData['title'],
                ],
                [
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'tenant_id' => 1,
                    'description' => $courseData['description'],
                    'level' => $courseData['difficulty_level'],
                    'price_kopecks' => $courseData['duration_hours'] * 5000, // 50 рублей за час
                    'corporate_price_kopecks' => $courseData['duration_hours'] * 3000, // 30 рублей за час для B2B
                    'syllabus' => json_encode(['modules' => []]),
                    'is_active' => true,
                    'correlation_id' => \Illuminate\Support\Str::uuid(),
                ]
            );

            VerticalCourse::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'vertical' => $courseData['vertical'],
                ],
                [
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'tenant_id' => 1,
                    'target_role' => $courseData['target_role'],
                    'difficulty_level' => $courseData['difficulty_level'],
                    'duration_hours' => $courseData['duration_hours'],
                    'is_required' => $courseData['is_required'],
                    'correlation_id' => \Illuminate\Support\Str::uuid(),
                ]
            );
        }
    }
}
