<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supermarket;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class B2BDocumentController
{
    /**
     * Скачать все документы заказа.
     */
    public function download(Request $request, int $orderId): JsonResponse
    {
        $order = DB::table('supermarket_orders')
            ->where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->where('is_b2b', true)
            ->first();

        if (!$order) {
            return response()->json([
                'error' => 'Order not found',
            ], 404);
        }

        $documents = json_decode($order->b2b_documents ?? '{}', true);

        if (empty($documents)) {
            return response()->json([
                'error' => 'No documents available',
                'message' => 'Documents are being generated. Please try again later.',
            ], 404);
        }

        return response()->json([
            'documents' => $documents,
        ]);
    }

    /**
     * Скачать счёт (Invoice).
     */
    public function downloadInvoice(Request $request, int $orderId): BinaryFileResponse|JsonResponse
    {
        $order = DB::table('supermarket_orders')
            ->where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->where('is_b2b', true)
            ->first();

        if (!$order) {
            return response()->json([
                'error' => 'Order not found',
            ], 404);
        }

        $documents = json_decode($order->b2b_documents ?? '{}', true);

        if (!isset($documents['invoice'])) {
            return response()->json([
                'error' => 'Invoice not available',
                'message' => 'Invoice is being generated. Please try again later.',
            ], 404);
        }

        $filePath = $documents['invoice'];

        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'error' => 'File not found',
            ], 404);
        }

        return Storage::disk('local')->download($filePath, basename($filePath));
    }

    /**
     * Скачать УПД.
     */
    public function downloadUPD(Request $request, int $orderId): BinaryFileResponse|JsonResponse
    {
        $order = DB::table('supermarket_orders')
            ->where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->where('is_b2b', true)
            ->first();

        if (!$order) {
            return response()->json([
                'error' => 'Order not found',
            ], 404);
        }

        $documents = json_decode($order->b2b_documents ?? '{}', true);

        if (!isset($documents['upd'])) {
            return response()->json([
                'error' => 'UPD not available',
                'message' => 'UPD is being generated. Please try again later.',
            ], 404);
        }

        $filePath = $documents['upd'];

        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'error' => 'File not found',
            ], 404);
        }

        return Storage::disk('local')->download($filePath, basename($filePath));
    }
}
