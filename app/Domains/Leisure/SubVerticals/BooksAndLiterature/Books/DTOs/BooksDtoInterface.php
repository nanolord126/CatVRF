<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\DTOs;

interface BooksDtoInterface
{
    public function toArray(): array;

    public static function fromJson(string $json): self;
}
