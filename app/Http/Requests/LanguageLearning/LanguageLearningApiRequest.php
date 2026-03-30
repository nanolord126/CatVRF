<?php declare(strict_types=1);

namespace App\Http\Requests\LanguageLearning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LanguageLearningApiRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            return true; // tenant scope handles isolation
        }

        public function rules(): array
        {
            if ($this->isMethod('POST') && $this->routeIs('*.enroll')) {
                return [
                    'course_id' => 'required|integer|exists:language_courses,id',
                    'student_id' => 'required|integer|exists:users,id',
                    'payment_method' => 'string|nullable',
                    'correlation_id' => 'required|string|uuid',
                ];
            }

            if ($this->isMethod('POST') && $this->routeIs('*.construct-path')) {
                return [
                    'language' => 'required|string|max:50',
                    'level' => 'required|string|max:10',
                    'goal' => 'string|max:100|nullable',
                    'weekly_hours' => 'required|integer|min:1|max:40',
                    'budget_limit' => 'integer|nullable',
                    'correlation_id' => 'required|string|uuid',
                ];
            }

            return [];
        }
}
