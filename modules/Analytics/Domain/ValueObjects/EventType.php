<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\ValueObjects;

final readonly class EventType
{
    public const PAGE_VIEW = 'page_view';
    public const CLICK = 'click';
    public const PURCHASE = 'purchase';
    public const ADD_TO_CART = 'add_to_cart';
    public const SEARCH = 'search';
    public const SIGN_UP = 'sign_up';
    public const LOGIN = 'login';
    public const LOGOUT = 'logout';

    private const VALID_TYPES = [
        self::PAGE_VIEW,
        self::CLICK,
        self::PURCHASE,
        self::ADD_TO_CART,
        self::SEARCH,
        self::SIGN_UP,
        self::LOGIN,
        self::LOGOUT,
    ];

    public function __construct(
        public string $value,
    ) {
        if (!in_array($this->value, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid event type: ' . $this->value);
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function pageView(): self
    {
        return new self(self::PAGE_VIEW);
    }

    public static function click(): self
    {
        return new self(self::CLICK);
    }

    public static function purchase(): self
    {
        return new self(self::PURCHASE);
    }

    public static function addToCart(): self
    {
        return new self(self::ADD_TO_CART);
    }

    public static function search(): self
    {
        return new self(self::SEARCH);
    }

    public static function signUp(): self
    {
        return new self(self::SIGN_UP);
    }

    public static function login(): self
    {
        return new self(self::LOGIN);
    }

    public static function logout(): self
    {
        return new self(self::LOGOUT);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
