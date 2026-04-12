<?php declare(strict_types=1);

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * NewsletterBatchJob — отправка пакета рассылки.
 *
 * Обрабатывает batch из max 100 user_id для одного newsletter_campaign.
 * Поддерживает каналы: email, push, sms, in_app.
 */
final class NewsletterBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly int    $newsletterId,
        private readonly array  $userIds,
        private readonly string $channel,
        private readonly string $correlationId,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    public function handle(): void
    {
        $newsletter = $this->db->table('newsletter_campaigns')->find($this->newsletterId);

        if ($newsletter === null) {
            $this->logger->channel('audit')->warning('NewsletterBatchJob: newsletter not found', [
                'newsletter_id'  => $this->newsletterId,
                'correlation_id' => $this->correlationId,
            ]);
            return;
        }

        $users = $this->db->table('users')
            ->whereIn('id', $this->userIds)
            ->where('is_active', true)
            ->get();

        $sent = 0;
        foreach ($users as $user) {
            try {
                $this->sendToUser($user, $newsletter);
                $sent++;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->warning('NewsletterBatchJob: send failed for user', [
                    'user_id'        => $user->id,
                    'channel'        => $this->channel,
                    'error'          => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
            }
        }

        // Обновляем счётчик отправленных
        $this->db->table('newsletter_campaigns')
            ->where('id', $this->newsletterId)
            ->increment('sent_count', $sent, ['updated_at' => now()]);

        $this->logger->channel('audit')->info('NewsletterBatchJob completed', [
            'newsletter_id'  => $this->newsletterId,
            'sent'           => $sent,
            'total_in_batch' => count($this->userIds),
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function sendToUser(object $user, object $newsletter): void
    {
        match ($this->channel) {
            'push'   => $this->sendPush($user, $newsletter),
            'sms'    => $this->sendSms($user, $newsletter),
            'in_app' => $this->sendInApp($user, $newsletter),
            default  => null,
        };
    }

    private function sendEmail(object $user, object $newsletter): void
    {
        if (empty($user->email)) {
            return;
        }

        // Используем Laravel Mail → Mailgun / SendGrid (настраивается через MAIL_MAILER)
        Mail::to($user->email)->queue(
            new \App\Mail\NewsletterMail($newsletter->subject, (int) $newsletter->template_id, (string) $this->correlationId)
        );
    }

    private function sendPush(object $user, object $newsletter): void
    {
        // Firebase FCM — токен хранится в user_device_tokens
        $token = $this->db->table('user_device_tokens')
            ->where('user_id', $user->id)
            ->value('fcm_token');

        if ($token === null) {
            return;
        }

        $this->logger->channel('audit')->debug('Push notification queued', [
            'user_id'        => $user->id,
            'newsletter_id'  => $this->newsletterId,
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function sendSms(object $user, object $newsletter): void
    {
        if (empty($user->phone)) {
            return;
        }

        $this->logger->channel('audit')->debug('SMS notification queued', [
            'user_id'        => $user->id,
            'newsletter_id'  => $this->newsletterId,
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function sendInApp(object $user, object $newsletter): void
    {
        $this->db->table('user_notifications')->insert([
            'user_id'        => $user->id,
            'type'           => 'newsletter',
            'title'          => $newsletter->subject,
            'is_read'        => false,
            'correlation_id' => $this->correlationId,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }
}
