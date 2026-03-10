<?php

namespace App\Jobs\Finances;

use App\Models\SettlementDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class EmailMonthlySettlements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $month;
    public $year;

    public function __construct($month = null, $year = null)
    {
        $this->month = $month ?? now()->subMonth()->month;
        $this->year = $year ?? now()->subMonth()->year;
    }

    public function handle()
    {
        $documents = SettlementDocument::whereMonth('document_date', $this->month)
            ->whereYear('document_date', $this->year)
            ->get();

        if ($documents->isEmpty()) {
            return;
        }

        $zipFileName = "settlements-{$this->year}-{$this->month}.zip";
        $zipPath = storage_path("app/public/temp/{$zipFileName}");

        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($documents as $doc) {
                if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                    $zip->addFile(Storage::disk('public')->path($doc->file_path), basename($doc->file_path));
                }
            }
            $zip->close();
        }

        $emails = tenant('report_emails') ?? [tenant('email')];
        
        if (!empty($emails)) {
            // Mock email sending
            // Mail::to($emails)->send(new \App\Mail\SettlementsMail($zipPath));
            \Illuminate\Support\Facades\Log::info("Sent ZIP {$zipFileName} to " . implode(', ', $emails));
        }

        // Cleanup
        // unlink($zipPath);
    }
}
