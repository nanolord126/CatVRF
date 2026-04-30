<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Jobs;

use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Domains\Supermarket\Models\B2BCompany;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * GenerateB2BDocumentsJob — генерация юридических документов для B2B заказов.
 *
 * Генерирует:
 * - Счёт на оплату (Invoice)
 * - УПД (Универсальный передаточный документ)
 * - Договор (Contract)
 */
final class GenerateB2BDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly int $orderId,
        public readonly array $documents,
        public readonly string $correlationId,
    ) {}

    public function handle(): void
    {
        $order = SupermarketOrder::findOrFail($this->orderId);
        $company = B2BCompany::findOrFail($order->b2b_company_id);

        $generatedDocuments = [];

        foreach ($this->documents as $documentType) {
            try {
                $filePath = match ($documentType) {
                    'invoice' => $this->generateInvoice($order, $company),
                    'upd' => $this->generateUPD($order, $company),
                    'contract' => $this->generateContract($order, $company),
                    default => null,
                };

                if ($filePath) {
                    $generatedDocuments[$documentType] = $filePath;
                }
            } catch (\Exception $e) {
                \Log::error("Failed to generate B2B document: {$documentType}", [
                    'order_id' => $this->orderId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
            }
        }

        // Save document paths to order
        if (!empty($generatedDocuments)) {
            $order->update([
                'b2b_documents' => json_encode($generatedDocuments),
            ]);
        }
    }

    private function generateInvoice(SupermarketOrder $order, B2BCompany $company): string
    {
        $invoiceNumber = 'INV-' . $order->id . '-' . now()->format('Ymd');
        $content = $this->getInvoiceContent($order, $company, $invoiceNumber);

        $fileName = "b2b/invoices/{$invoiceNumber}.pdf";
        Storage::disk('local')->put($fileName, $content);

        return $fileName;
    }

    private function generateUPD(SupermarketOrder $order, B2BCompany $company): string
    {
        $updNumber = 'UPD-' . $order->id . '-' . now()->format('Ymd');
        $content = $this->getUPDContent($order, $company, $updNumber);

        $fileName = "b2b/upd/{$updNumber}.pdf";
        Storage::disk('local')->put($fileName, $content);

        return $fileName;
    }

    private function generateContract(SupermarketOrder $order, B2BCompany $company): string
    {
        $contractNumber = 'CTR-' . $order->id . '-' . now()->format('Ymd');
        $content = $this->getContractContent($order, $company, $contractNumber);

        $fileName = "b2b/contracts/{$contractNumber}.pdf";
        Storage::disk('local')->put($fileName, $content);

        return $fileName;
    }

    private function getInvoiceContent(SupermarketOrder $order, B2BCompany $company, string $number): string
    {
        return "Счёт №{$number}\n" .
               "Компания: {$company->company_name}\n" .
               "ИНН: {$company->inn}\n" .
               "Сумма: {$order->total_amount / 100} RUB\n" .
               "Дата: " . now()->format('d.m.Y');
    }

    private function getUPDContent(SupermarketOrder $order, B2BCompany $company, string $number): string
    {
        return "УПД №{$number}\n" .
               "Компания: {$company->company_name}\n" .
               "ИНН: {$company->inn}\n" .
               "Сумма: {$order->total_amount / 100} RUB";
    }

    private function getContractContent(SupermarketOrder $order, B2BCompany $company, string $number): string
    {
        return "Договор №{$number}\n" .
               "Компания: {$company->company_name}\n" .
               "ИНН: {$company->inn}\n";
    }
}
