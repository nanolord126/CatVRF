<?php declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIBookConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly AIConstructorService $aiService,
            private readonly RecommendationService $baseRecService
        ) {}

        /**
         * Generate a personalized Book Reading Plan/Recommendation.
         * Maps user preferences and mood to historical data and available stock.
         */
        public function generateReadingPlan(BookAIRequestDto $dto): array
        {
            $correlationId = $dto->correlationId ?? (string) Str::uuid();

            Log::channel('audit')->info('AI Book Consultation STARTED', [
                'user_id' => $dto->userId,
                'mood' => $dto->currentMood,
                'cid' => $correlationId
            ]);

            // 1. Fetch available books based on preferred genres and mood
            $query = Book::with(['author', 'genre'])
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0);

            if (!empty($dto->preferredGenres)) {
                $query->whereIn('genre_id', $dto->preferredGenres);
            }

            // 2. Filter by Era or Mood Focus via metadata (simulated heuristic mapping)
            $candidates = $query->get()->filter(function (Book $book) use ($dto) {
                $metaMood = (array) ($book->metadata['mood_tags'] ?? []);

                // Mood Matching Logic:
                // If user is 'curious', we want 'informative', 'mind-bending', or 'complex'
                // If user is 'stressed', we want 'comfort', 'light', 'escapist'
                $moodMap = [
                    'curious' => ['informative', 'deep', 'complex', 'mystery'],
                    'stressed' => ['comfort', 'humor', 'light', 'short'],
                    'adventurous' => ['action', 'epic', 'thriller', 'journey'],
                    'intellectual' => ['history', 'biography', 'philosophy', 'science']
                ];

                $supportedMoods = $moodMap[$dto->currentMood] ?? ['general'];

                // Check intersection of book mood tags and user mood map
                return !empty(array_intersect($metaMood, $supportedMoods));
            });

            // 3. Score candidates using AI Recommendation Logic
            $scoredBooks = $candidates->map(function (Book $book) use ($dto) {
                $score = $this->baseRecService->scoreItem($dto->userId, $book->id, [
                    'vertical' => 'Books',
                    'reading_level' => $dto->readingLevel,
                    'biography_focus' => $dto->biographyFocus
                ]);

                return [
                    'book_id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author->name,
                    'genre' => $book->genre->name,
                    'score' => $score,
                    'reason' => $this->getAIReasonPhrase($book, $dto->currentMood)
                ];
            })->sortByDesc('score')->take(5)->values();

            // 4. Generate Reading Roadmap (e.g. order of reading)
            $roadmap = [];
            foreach ($scoredBooks as $index => $item) {
                $roadmap[] = [
                    'step' => $index + 1,
                    'title' => $item['title'],
                    'suggestion' => "Recommended because you are feeling " . $dto->currentMood . "."
                ];
            }

            $finalResponse = [
                'success' => true,
                'context' => [
                    'user_id' => $dto->userId,
                    'mood' => $dto->currentMood,
                    'reading_level' => $dto->readingLevel
                ],
                'recommendations' => $scoredBooks,
                'reading_roadmap' => $roadmap,
                'correlation_id' => $correlationId
            ];

            Log::channel('audit')->info('AI Book Consultation COMPLETED', [
                'user_id' => $dto->userId,
                'rec_count' => count($scoredBooks),
                'cid' => $correlationId
            ]);

            return $finalResponse;
        }

        /**
         * Map mood to specific terminology in the AI response.
         */
        private function getAIReasonPhrase(Book $book, string $mood): string
        {
            $phrases = [
                'curious' => "This book offers a deep dive into its subject, perfect for your inquisitive state.",
                'stressed' => "A gentle and engaging narrative that helps in unwinding and relaxation.",
                'adventurous' => "Fast-paced and thrilling, matching your energetic vibe today.",
                'intellectual' => "Rich in detail and complexity, this will challenge your current perspective."
            ];

            return $phrases[$mood] ?? "A highly rated selection matching your overall reading habits.";
        }
}
