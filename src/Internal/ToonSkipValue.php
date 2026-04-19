<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Internal;

/**
 * Internal singleton sentinel used by replacer callbacks to remove values.
 */
final class ToonSkipValue
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }
}

