<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ArchiveInactiveChannelsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    CHANNEL_ARCHIVE_INACTIVE_DAYS} дней — архивируется.
     * За 14 дней до архивации отправляется предупреждение.
     */
    final class ArchiveInactiveChannelsJob implements ShouldQueue
    {
        use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;

        public function __construct(
            private readonly string $correlationId = '',
        ) {}

        public function handle(ChannelService $channelService): void
        {
            $correlationId    = $this->correlationId ?: Str::uuid()->toString();
            $inactiveDays     = config('channels.archive.inactive_days', 90);
            $warnBeforeDays   = config('channels.archive.warn_before_days', 14);
            $threshold        = now()->subDays($inactiveDays);
            $warnThreshold    = now()->subDays($inactiveDays - $warnBeforeDays);

            Log::channel('audit')->info('ArchiveInactiveChannelsJob started', [
                'correlation_id' => $correlationId,
                'inactive_days'  => $inactiveDays,
                'threshold'      => $threshold->toIso8601String(),
            ]);

            // 1. Отправить предупреждения за 14 дней
            $warnCount = 0;
            BusinessChannel::withoutGlobalScopes()
                ->where('status', 'active')
                ->where(fn ($q) => $q
                    ->whereNull('last_post_at')
                    ->orWhere('last_post_at', '<=', $warnThreshold)
                )
                ->where(fn ($q) => $q
                    ->whereNull('last_post_at')
                    ->orWhere('last_post_at', '>', $threshold)
                )
                ->chunk(100, function ($channels) use ($correlationId, &$warnCount): void {
                    foreach ($channels as $channel) {
                        try {
                            event(new \App\Domains\Content\Channels\Events\ChannelArchived(
                                $channel,
                                'inactivity_warning',
                                $correlationId
                            ));
                            $warnCount++;
                        } catch (\Throwable $e) {
                            Log::channel('audit')->error('Failed to send archive warning', [
                                'correlation_id' => $correlationId,
                                'channel_id'     => $channel->id,
                                'error'          => $e->getMessage(),
                            ]);
                        }
                    }
                });

            // 2. Архивировать неактивные (90+ дней без постов)
            $archivedCount = 0;
            BusinessChannel::withoutGlobalScopes()
                ->where('status', 'active')
                ->where(fn ($q) => $q
                    ->whereNull('last_post_at')
                    ->orWhere('last_post_at', '<=', $threshold)
                )
                ->chunk(100, function ($channels) use ($channelService, $correlationId, &$archivedCount): void {
                    foreach ($channels as $channel) {
                        try {
                            $channelService->archiveChannel(
                                $channel,
                                'auto_inactivity_90_days',
                                $correlationId
                            );
                            $archivedCount++;
                        } catch (\Throwable $e) {
                            Log::channel('audit')->error('Failed to archive channel', [
                                'correlation_id' => $correlationId,
                                'channel_id'     => $channel->id,
                                'error'          => $e->getMessage(),
                                'trace'          => $e->getTraceAsString(),
                            ]);
                        }
                    }
                });

            Log::channel('audit')->info('ArchiveInactiveChannelsJob completed', [
                'correlation_id' => $correlationId,
                'archived'       => $archivedCount,
                'warned'         => $warnCount,
            ]);
        }

        public function tags(): array
        {
            return ['channel', 'archive', 'scheduled'];
        }
}
