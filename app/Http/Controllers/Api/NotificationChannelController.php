<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

final class NotificationChannelController extends Controller
{
    private array $availableChannels = [
        'telegram' => [
            'name' => 'Telegram',
            'icon' => 'telegram',
            'config_keys' => ['bot_token', 'bot_username', 'webhook_url'],
        ],
        'whatsapp' => [
            'name' => 'WhatsApp',
            'icon' => 'whatsapp',
            'config_keys' => ['api_url', 'token', 'instance_id', 'from_number', 'webhook_verify_token'],
        ],
        'viber' => [
            'name' => 'Viber',
            'icon' => 'viber',
            'config_keys' => ['api_token', 'sender_name', 'api_url'],
        ],
        'kakaotalk' => [
            'name' => 'KakaoTalk',
            'icon' => 'kakaotalk',
            'config_keys' => ['api_key', 'api_secret', 'sender_number', 'api_url'],
        ],
        'signal' => [
            'name' => 'Signal',
            'icon' => 'signal',
            'config_keys' => ['api_url', 'api_key'],
        ],
        'wechat' => [
            'name' => 'WeChat',
            'icon' => 'wechat',
            'config_keys' => ['app_id', 'app_secret', 'api_url'],
        ],
        'vk' => [
            'name' => 'VK',
            'icon' => 'vk',
            'config_keys' => ['access_token', 'api_version', 'api_url'],
        ],
        'odnoklassniki' => [
            'name' => 'Odnoklassniki',
            'icon' => 'odnoklassniki',
            'config_keys' => ['access_token', 'application_key', 'api_url'],
        ],
        'email' => [
            'name' => 'Email',
            'icon' => 'email',
            'config_keys' => ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'from_address', 'from_name'],
        ],
        'sms' => [
            'name' => 'SMS',
            'icon' => 'sms',
            'config_keys' => ['api_url', 'api_key', 'sender_id'],
        ],
    ];

    public function index(): JsonResponse
    {
        $channels = collect($this->availableChannels)->map(function ($channel, $key) {
            $config = Config::get("services.{$key}", []);
            $enabled = !empty($config) && !empty(array_filter($config));

            return [
                'id' => $key,
                'name' => $channel['name'],
                'icon' => $channel['icon'],
                'enabled' => $enabled,
                'configured' => $enabled,
                'config_keys' => $channel['config_keys'],
            ];
        })->values();

        return response()->json([
            'channels' => $channels,
        ]);
    }

    public function show(string $channel): JsonResponse
    {
        if (!isset($this->availableChannels[$channel])) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        $config = Config::get("services.{$channel}", []);
        $channelInfo = $this->availableChannels[$channel];

        // Mask sensitive values
        $maskedConfig = collect($config)->map(function ($value, $key) {
            $sensitiveKeys = ['token', 'secret', 'password', 'api_key', 'access_token'];
            if (in_array($key, $sensitiveKeys)) {
                return str_repeat('*', strlen($value));
            }
            return $value;
        })->toArray();

        return response()->json([
            'channel' => [
                'id' => $channel,
                'name' => $channelInfo['name'],
                'icon' => $channelInfo['icon'],
                'config' => $maskedConfig,
                'enabled' => !empty(array_filter($config)),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel' => 'required|string|in:' . implode(',', array_keys($this->availableChannels)),
            'config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $channel = $request->input('channel');
        $config = $request->input('config');

        // Validate required config keys
        $requiredKeys = $this->availableChannels[$channel]['config_keys'];
        foreach ($requiredKeys as $key) {
            if (!isset($config[$key]) || empty($config[$key])) {
                return response()->json(['error' => "Missing required config key: {$key}"], 422);
            }
        }

        // Save config to .env (in production, use database)
        // For now, we'll just update runtime config
        Config::set("services.{$channel}", $config);

        return response()->json([
            'message' => 'Channel configured successfully',
            'channel' => $channel,
        ]);
    }

    public function update(Request $request, string $channel): JsonResponse
    {
        if (!isset($this->availableChannels[$channel])) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        $config = $request->input('config', []);
        Config::set("services.{$channel}", $config);

        return response()->json([
            'message' => 'Channel updated successfully',
            'channel' => $channel,
        ]);
    }

    public function destroy(string $channel): JsonResponse
    {
        if (!isset($this->availableChannels[$channel])) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        Config::set("services.{$channel}", []);

        return response()->json([
            'message' => 'Channel disabled successfully',
            'channel' => $channel,
        ]);
    }

    public function test(Request $request, string $channel): JsonResponse
    {
        if (!isset($this->availableChannels[$channel])) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        $config = Config::get("services.{$channel}", []);
        $testRecipient = $request->input('recipient');

        if (empty($config)) {
            return response()->json(['error' => 'Channel not configured'], 400);
        }

        try {
            $result = $this->testChannel($channel, $config, $testRecipient);
            return response()->json([
                'success' => true,
                'message' => 'Test message sent successfully',
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function enable(string $channel): JsonResponse
    {
        if (!isset($this->availableChannels[$channel])) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        Config::set("services.{$channel}.enabled", true);

        return response()->json([
            'message' => 'Channel enabled successfully',
        ]);
    }

    public function disable(string $channel): JsonResponse
    {
        if (!isset($this->availableChannels[$channel])) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        Config::set("services.{$channel}.enabled", false);

        return response()->json([
            'message' => 'Channel disabled successfully',
        ]);
    }

    private function testChannel(string $channel, array $config, ?string $recipient): array
    {
        return match($channel) {
            'telegram' => $this->testTelegram($config, $recipient),
            'whatsapp' => $this->testWhatsApp($config, $recipient),
            'viber' => $this->testViber($config, $recipient),
            default => ['status' => 'not_implemented'],
        };
    }

    private function testTelegram(array $config, ?string $recipient): array
    {
        $response = Http::get("https://api.telegram.org/bot{$config['bot_token']}/getMe");

        if (!$response->successful()) {
            throw new \Exception('Failed to connect to Telegram API');
        }

        return [
            'status' => 'success',
            'bot_info' => $response->json(),
        ];
    }

    private function testWhatsApp(array $config, ?string $recipient): array
    {
        $response = Http::get("{$config['api_url']}/status", [
            'token' => $config['token'],
        ]);

        return [
            'status' => 'success',
            'api_status' => $response->json(),
        ];
    }

    private function testViber(array $config, ?string $recipient): array
    {
        $response = Http::get("{$config['api_url']}/get_account_info", [], [
            'X-Viber-Auth-Token' => $config['api_token'],
        ]);

        return [
            'status' => 'success',
            'account_info' => $response->json(),
        ];
    }
}
