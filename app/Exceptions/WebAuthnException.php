<?php

declare(strict_types=1);

namespace App\Exceptions;

final class WebAuthnException extends \RuntimeException
{
    public static function challengeExpired(): self
    {
        return new self('Challenge expired or invalid');
    }

    public static function credentialNotFound(): self
    {
        return new self('Credential not found');
    }

    public static function replayAttack(): self
    {
        return new self('Replay attack detected - counter did not increase');
    }

    public static function userMismatch(): self
    {
        return new self('Credential user mismatch');
    }

    public static function originMismatch(): self
    {
        return new self('Origin mismatch');
    }

    public static function invalidType(): self
    {
        return new self('Invalid type in clientDataJSON');
    }

    public static function missingClientData(): self
    {
        return new self('Missing clientDataJSON');
    }

    public static function missingChallenge(): self
    {
        return new self('Missing challenge in clientDataJSON');
    }

    public static function challengeMismatch(): self
    {
        return new self('Challenge mismatch');
    }

    public static function missingSignature(): self
    {
        return new self('Missing signature');
    }

    public static function invalidAttestation(): self
    {
        return new self('Invalid attestation data');
    }

    public static function credentialAlreadyRegistered(): self
    {
        return new self('Credential already registered');
    }

    public static function cannotDeleteLastPasskey(): self
    {
        return new self('Cannot delete the last passkey. Add another passkey first.');
    }
}
