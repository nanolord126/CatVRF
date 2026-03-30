<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicAIConstructorService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly RecommendationService $recommendation,
        ) {}

        /**
         * AI Instrument & Lesson Constructor.
         * Recommendation based on level, genre, and budget.
         */
        public function constructForUser(string $level, string $genre, int $budgetCents, int $userId): array
        {
            FraudControlService::check();

            $correlationId = (string) Str::uuid();

            // 1. Filter instruments based on budget and genre
            $instruments = MusicInstrument::where('price_cents', '<=', $budgetCents)
                ->where('tags', 'like', '%' . $genre . '%')
                ->where('stock', '>', 0)
                ->limit(5)
                ->get();

            // 2. Filter lessons based on level and genre
            $lessons = MusicLesson::where('level', $level)
                ->where('subject', 'like', '%' . $genre . '%')
                ->limit(5)
                ->get();

            // 3. Score overall recommendations using ML system
            $scoredInstruments = $instruments->map(function ($instrument) use ($userId, $level) {
                $score = $this->recommendation->scoreItem($userId, $instrument->id, [
                    'level' => $level,
                    'vertical' => 'music',
                ]);
                return [
                    'item' => $instrument,
                    'score' => $score,
                ];
            })->sortByDesc('score');

            $scoredLessons = $lessons->map(function ($lesson) use ($userId, $level) {
                $score = $this->recommendation->scoreItem($userId, $lesson->id, [
                    'level' => $level,
                    'vertical' => 'music_lessons',
                ]);
                return [
                    'item' => $lesson,
                    'score' => $score,
                ];
            })->sortByDesc('score');

            Log::channel('audit')->info('Music AI Constructor used', [
                'user_id' => $userId,
                'level' => $level,
                'genre' => $genre,
                'budget' => $budgetCents,
                'correlation_id' => $correlationId,
            ]);

            return [
                'correlation_id' => $correlationId,
                'instruments' => $scoredInstruments->values(),
                'lessons' => $scoredLessons->values(),
            ];
        }

        /**
         * Save AI construction results to user profile.
         */
        public function saveAIResults(int $userId, array $results): void
        {
            DB::table('user_ai_designs')->insert([
                'user_id' => $userId,
                'vertical' => 'music',
                'design_data' => json_encode($results),
                'correlation_id' => $results['correlation_id'],
                'created_at' => now(),
            ]);

            Log::channel('audit')->info('Music AI Construction saved', [
                'user_id' => $userId,
                'correlation_id' => $results['correlation_id'],
            ]);
        }
}
