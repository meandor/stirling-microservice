<?php

namespace Stirling\Core;

class ConfigurationMasker
{
    public const MASK = "********";

    private array $patterns;

    /**
     * @param string[] $patterns
     */
    public function __construct(array $patterns = ["password", "secret", "token", "key", "credential"])
    {
        $this->patterns = $patterns;
    }

    /**
     * @param array<mixed> $configuration
     * @return array<mixed>
     */
    public function mask(array $configuration): array
    {
        $masked = [];
        foreach ($configuration as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $masked[$key] = self::MASK;
            } elseif (is_array($value)) {
                $masked[$key] = $this->mask($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach ($this->segmentsOf($key) as $segment) {
            if (in_array($segment, $this->patterns, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function segmentsOf(string $key): array
    {
        $segments = preg_split('/[_-]/', $key);

        $splitSegments = [];
        foreach ($segments as $segment) {
            $splitSegments = array_merge(
                $splitSegments,
                preg_split('/(?<=[a-z])(?=[A-Z])/', $segment)
            );
        }

        return array_map('strtolower', $splitSegments);
    }
}
