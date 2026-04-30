<?php

declare(strict_types=1);

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Models\BusinessGroup;
use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * B2BDocumentController — Управление документами B2B (счёта, договоры, акты).
 *
 * Все методы требуют X-B2B-API-Key (через B2BApiMiddleware).
 * business_group доступен через $request->attributes->get('b2b_business_group').
 */
final class B2BDocumentController extends Controller
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ResponseFactory $response,
        private readonly AuditService $audit,
    ) {}

    /** Получить список документов. */
    public function index(Request $request): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $type = $request->input('type');
        $status = $request->input('status');

        $query = $this->db->table('b2b_documents')
            ->where('business_group_id', $group->id);

        if ($type) {
            $query->where('type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $documents = $query->orderByDesc('created_at')->paginate(50);

        return $this->response->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    /** Создать новый документ. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:invoice,invoice_factura,contract,act,upd,compliance'],
            'name' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:100'],
            'order_id' => ['sometimes', 'integer', 'min:1'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'due_date' => ['sometimes', 'date'],
            'valid_until' => ['sometimes', 'date'],
        ]);

        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        // Загрузка файла
        $file = $request->file('file');
        $filePath = $file->store('b2b/documents/' . $group->id, 'public');
        $fileSize = $file->getSize();

        $documentId = $this->db->table('b2b_documents')->insertGetId([
            'business_group_id' => $group->id,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'number' => $validated['number'],
            'order_id' => $validated['order_id'] ?? null,
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $fileSize,
            'amount' => $validated['amount'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'status' => 'pending',
            'signed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logCreated('b2b_document', [
            'document_id' => $documentId,
            'business_group_id' => $group->id,
            'type' => $validated['type'],
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'data' => ['id' => $documentId],
            'correlation_id' => $correlationId,
        ], 201);
    }

    /** Получить детальную информацию о документе. */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $document = $this->db->table('b2b_documents')
            ->where('id', $id)
            ->where('business_group_id', $group->id)
            ->first();

        if (!$document) {
            return $this->response->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        return $this->response->json([
            'success' => true,
            'data' => $document,
        ]);
    }

    /** Подписать документ. */
    public function sign(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'in:electronic,sms'],
            'comment' => ['sometimes', 'string', 'max:500'],
        ]);

        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $updated = $this->db->table('b2b_documents')
            ->where('id', $id)
            ->where('business_group_id', $group->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'signed',
                'signed' => true,
                'signed_at' => now(),
                'sign_method' => $validated['method'],
                'sign_comment' => $validated['comment'] ?? null,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return $this->response->json(['success' => false, 'message' => 'Document not found or cannot be signed'], 422);
        }

        $this->logAction('b2b_document_signed', [
            'document_id' => $id,
            'business_group_id' => $group->id,
            'method' => $validated['method'],
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'correlation_id' => $correlationId,
        ]);
    }

    /** Удалить документ. */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var BusinessGroup $group */
        $group = $request->attributes->get('b2b_business_group');

        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();

        $document = $this->db->table('b2b_documents')
            ->where('id', $id)
            ->where('business_group_id', $group->id)
            ->first();

        if (!$document) {
            return $this->response->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        // Удалить файл
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $this->db->table('b2b_documents')
            ->where('id', $id)
            ->delete();

        $this->logDeleted('b2b_document', [
            'document_id' => $id,
            'business_group_id' => $group->id,
            'correlation_id' => $correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'correlation_id' => $correlationId,
        ]);
    }
}
