<?php declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Controllers;



use Illuminate\Contracts\Routing\ResponseFactory;
use Psr\Log\LoggerInterface;
use App\Domains\BooksAndLiterature\Models\Book;
use App\Domains\BooksAndLiterature\Http\Resources\BookResource;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
final class BookController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Book::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $items = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return BookResource::collection($items);
    }

    public function show(int $id): BookResource
    {
        $model = Book::findOrFail($id);

        return new BookResource($model);
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'books_and_literature_create',
            amount: 0,
            correlationId: $correlationId,
        );

        $model = $this->db->transaction(function () use ($request, $correlationId) {
            $item = Book::create(array_merge(
                $request->validated(),
                [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $request->user()->tenant_id,
                ]
            ));

            $this->logger->info('Book created', [
                'id' => $item->id,
                'correlation_id' => $correlationId,
                'tenant_id' => $request->user()->tenant_id,
            ]);

            return $item;
        });

        return (new BookResource($model))
            ->response()
            ->setStatusCode(201)
            ->header('X-Correlation-ID', $correlationId);
    }

    public function update(Request $request, int $id): BookResource
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $model = Book::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'books_and_literature_update',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $request, $correlationId) {
            $model->update($request->validated());

            $this->logger->info('Book updated', [
                'id' => $model->id,
                'correlation_id' => $correlationId,
            ]);
        });

        return new BookResource($model->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $model = Book::findOrFail($id);

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'books_and_literature_delete',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($model, $correlationId) {
            $model->delete();

            $this->logger->info('Book deleted', [
                'id' => $model->id,
                'correlation_id' => $correlationId,
            ]);
        });

        return (new \Illuminate\Http\JsonResponse(['message' => 'Deleted'], 200))
            ->header('X-Correlation-ID', $correlationId);
    }
}