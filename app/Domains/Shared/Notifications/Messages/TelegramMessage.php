<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Messages;

final readonly class TelegramMessage
{
    public function __construct(
        public string $content,
        public ?array $keyboard = null
    ) {}

    public static function create(string $content): self
    {
        return new self($content);
    }

    public function withKeyboard(array $keyboard): self
    {
        return new self($this->content, $keyboard);
    }

    public function line(string $line): self
    {
        return new self($this->content . $line . "\n", $this->keyboard);
    }
}
