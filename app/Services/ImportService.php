<?php
namespace App\Services;
use App\Models\ImportTrack;
use App\Jobs\ProcessImportJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImportService {
    public function submit(UploadedFile $file, string $vertical, int $userId) {
        $path = $file->store('imports/temp');
        $track = ImportTrack::create([
            'user_id' => $userId,
            'file_path' => $path,
            'vertical' => $vertical,
            'correlation_id' => (string) Str::uuid()
        ]);
        ProcessImportJob::dispatch($track);
        return $track;
    }
    public function getStatus(string $correlationId) {
        return ImportTrack::where('correlation_id', $correlationId)->first();
    }
}
