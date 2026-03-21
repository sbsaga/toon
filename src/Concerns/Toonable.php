<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Concerns;

/**
 * Opt-in trait for models and DTOs that want convenient TOON serialization.
 */
trait Toonable
{
    /**
     * Encode the current object as TOON.
     */
    public function toToon(): string
    {
        return toon_encode($this->toonPayload());
    }

    /**
     * Wrap the current object in a fenced TOON markdown block.
     */
    public function toToonPrompt(string $label = 'toon'): string
    {
        return toon_prompt($this->toonPayload(), $label);
    }

    /**
     * Resolve the most appropriate payload representation for TOON serialization.
     *
     * @return array<string, mixed>
     */
    protected function toonPayload(): array
    {
        if (method_exists($this, 'toArray')) {
            $data = $this->toArray();
            if (is_array($data)) {
                return $data;
            }
        }

        /** @var array<string, mixed> $vars */
        $vars = get_object_vars($this);

        return $vars;
    }
}
