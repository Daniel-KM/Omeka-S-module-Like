<?php declare(strict_types=1);

namespace 🖒\Stdlib;

use Laminas\Http\Header\SetCookie;

/**
 * Helper to identify anonymous voters via a persistent cookie token.
 *
 * A random token is stored in a long-lived cookie and its hash is stored in the
 * database as the like identity, so an anonymous visitor cannot vote twice on
 * the same resource from the same browser. This is a best-effort deduplication:
 * clearing cookies or switching browser bypasses it.
 */
final class Anonymous
{
    const COOKIE_NAME = 'like_anonymous';

    const COOKIE_LIFETIME = 31536000;

    /**
     * Get the anonymous token from the current request cookie, if any.
     */
    public static function token(): ?string
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? null;
        return is_string($token) && $token !== '' ? $token : null;
    }

    /**
     * Generate a new random anonymous token.
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Hash a token into the stored identity.
     */
    public static function identity(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Build the anonymous cookie. Use an empty value with a past expiry to
     * delete it.
     */
    public static function cookie(string $value, int $expires, string $path, bool $secure): SetCookie
    {
        return new SetCookie(
            self::COOKIE_NAME,
            $value,
            $expires,
            $path,
            null,
            $secure,
            true,
            null,
            null,
            'Lax'
        );
    }
}
