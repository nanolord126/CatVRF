<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * VideoAvatarService — сервис генерации видео-аватаров из фото.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Генерирует анимированные аватары 4-6 секунд без звука из статичных фото.
 * Использует FFmpeg для создания видео с эффектами.
 */
final readonly class VideoAvatarService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Генерация видео-аватара из фото.
     *
     * @param Staff $staff Сотрудник
     * @param int $duration Длительность в секундах (4-6)
     * @param string $effect Эффект анимации (zoom, pan, fade, bounce)
     * @return string|null URL сгенерированного видео
     */
    public function generateFromPhoto(Staff $staff, int $duration = 5, string $effect = 'zoom'): ?string
    {
        if (!$staff->photo_url || !$staff->photo_path) {
            $this->logger->warning('Cannot generate video avatar: no photo', [
                'staff_id' => $staff->id,
            ]);
            return null;
        }

        try {
            // Обновляем статус
            $staff->update([
                'video_avatar_status' => 'processing',
            ]);
 v v
            $photoPath =  toragesv('public')->path($staff->photo_path);
  $outputPath = "staff-video-avatars/{$staff->uuid}_avatar.mp4";
  $outputFullPath = Storage::disk('public')->path($outputPath);

            // Проверяем наличие FFmpeg
            if (!$this->checkFFmpegAvailable()) {
                $this->logger->error('FFmpeg not available', [
                    'staff_id' => $staff->id,
                ]);
                $staff->update([
                    'video_avatar_status' => 'failed',
                ]);
                return null;
            }

            // Генерируем видео в зависимости от эффекта
            $command = $this->buildFFmpegCommand($photoPath, $outputFullPath, $duration, $effect);
            
            $this->logger->info('Generating video avatar', [
                'staff_id' => $staff->id,
                'command' => $command,
            ]);

            $exitCode = $this->executeCommand($command);

            if ($exitCode === 0 && file_exists($outputFullPath)) {
                $videoUrl = Storage::disk('public')->url($outputPath);
                $staff->update([
                    'video_avatar_url' => $videoUrl,
                    'video_avatar_path' => $outputPath,
                    'video_avatar_generated' => true,
                    'video_avatar_generated_at' => now(),
                    'video_avatar_status' => 'completed',
                ]);

                $this->logger->info('Video avatar generated successfully', [
                    'staff_id' => $staff->id,
                    'output_path' => $outputPath,
                ]);

                return $videoUrl;
            }

            $staff->update([
                'video_avatar_status' => 'failed',
            ]);

            $this->logger->error('Failed to generate video avatar', [
                'staff_id' => $staff->id,
                'exit_code' => $exitCode,
            ]);

            return null;
        } catch (\Exception $e) {
            $staff->update([
                'video_avatar_status' => 'failed',
            ]);

            $this->logger->error('Error generating video avatar', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Проверка доступности FFmpeg.
     */
    private function checkFFmpegAvailable(): bool
    {
        $output = shell_exec('which ffmpeg');
        return !empty($output);
    }

    /**
     * Построение команды FFmpeg для генерации видео.
     */
    private function buildFFmpegCommand(string $inputPath, string $outputPath, int $duration, string $effect): string
    {
        $durationArg = "-t {$duration}";
        $sizeArg = "-vf scale=320:320";
        $fpsArg = "-r 30";

        $fadeEndTime = $duration - 1;

        $effectFilter = match ($effect) {
            'zoom' => "zoompan=z='min(zoom+0.0015,1.5)':d=125:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)'",
            'pan' => "pan='iw:ih:0:0',fade=t=out:st=4:d=1",
            'fade' => "fade=t=in:st=0:d=1,fade=t=out:st={$fadeEndTime}:d=1",
            'bounce' => "scale=iw*1.5:ih*1.5,crop=iw:ih:(iw-iw)/2:(ih-ih)/2",
            default => "zoompan=z='min(zoom+0.0015,1.5)':d=125",
        };

        return sprintf(
            'ffmpeg -y -loop 1 -i %s %s -vf "%s,%s" -c:v libx264 -tune stillimage -preset ultrafast -crf 23 -pix_fmt yuv420p %s %s %s',
            escapeshellarg($inputPath),
            $durationArg,
            $effectFilter,
            $sizeArg,
            $fpsArg,
            escapeshellarg($outputPath)
        );
    }

    /**
     * Выполнение команды shell.
     */
    private function executeCommand(string $command): int
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            return proc_close($process);
        }

        return 1;
    }

    /**
     * Массовая генерация видео-аватаров для всех сотрудников.
     *
     * @param int $limit Лимит сотрудников для обработки
     * @return array Результаты генерации
     */
    public function generateBatch(int $limit = 10): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $staffMembers = Staff::whereNotNull('photo_path')
            ->where('video_avatar_generated', false)
            ->where('video_avatar_status', '!=', 'processing')
            ->limit($limit)
            ->get();

        foreach ($staffMembers as $staff) {
            $result = $this->generateFromPhoto($staff);

            if ($result) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Регенерация видео-аватара для конкретного сотрудника.
     */
    public function regenerate(Staff $staff, string $effect = 'zoom'): ?string
    {
        // Удаляем старое видео если есть
        if ($staff->video_avatar_path && Storage::disk('public')->exists($staff->video_avatar_path)) {
            Storage::disk('public')->delete($staff->video_avatar_path);
        }

        // Сбрасываем статус
        $staff->update([
            'video_avatar_generated' => false,
            'video_avatar_status' => 'pending',
        ]);

        return $this->generateFromPhoto($staff, 5, $effect);
    }

    /**
     * Удаление видео-аватара.
     */
    public function delete(Staff $staff): bool
    {
        if ($staff->video_avatar_path && Storage::disk('public')->exists($staff->video_avatar_path)) {
            Storage::disk('public')->delete($staff->video_avatar_path);
        }

        $staff->update([
            'video_avatar_url' => null,
            'video_avatar_path' => null,
            'video_avatar_generated' => false,
            'video_avatar_generated_at' => null,
            'video_avatar_status' => 'pending',
        ]);

        return true;
    }
}
