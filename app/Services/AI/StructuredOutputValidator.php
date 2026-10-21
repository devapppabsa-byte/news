<?php

namespace App\Services\AI;

use App\Exceptions\AIProviderException;

class StructuredOutputValidator
{
    /**
     * Validate a decoded payload against a minimal schema description.
     *
     * Supported schema keys:
     * - type: "object" (default) | "array" | "string" | "integer" | "number" | "boolean"
     * - required: array<int, string> (for objects)
     * - properties: array<string, array<string, mixed>> (for objects)
     * - items: array<string, mixed> (for arrays)
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     *
     * @throws AIProviderException
     */
    public function validate(array $data, array $schema, string $provider): array
    {
        $this->check($data, $schema, $provider, 'root');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $schema
     *
     * @throws AIProviderException
     */
    protected function check(mixed $value, array $schema, string $provider, string $path): void
    {
        $type = $schema['type'] ?? 'object';

        switch ($type) {
            case 'object':
                if (! is_array($value)) {
                    $this->fail($provider, "$path must be an object");
                }

                foreach ($schema['required'] ?? [] as $key) {
                    if (! array_key_exists($key, $value)) {
                        $this->fail($provider, "$path is missing required key [$key]");
                    }
                }

                foreach ($schema['properties'] ?? [] as $key => $childSchema) {
                    if (array_key_exists($key, $value)) {
                        $this->check($value[$key], $childSchema, $provider, "$path.$key");
                    }
                }
                break;

            case 'array':
                if (! is_array($value) || ! array_is_list($value)) {
                    $this->fail($provider, "$path must be a list");
                }

                if (isset($schema['items'])) {
                    foreach ($value as $index => $item) {
                        $this->check($item, $schema['items'], $provider, "$path.$index");
                    }
                }
                break;

            case 'string':
                if (! is_string($value)) {
                    $this->fail($provider, "$path must be a string");
                }
                break;

            case 'integer':
                if (! is_int($value)) {
                    $this->fail($provider, "$path must be an integer");
                }
                break;

            case 'number':
                if (! is_int($value) && ! is_float($value)) {
                    $this->fail($provider, "$path must be a number");
                }
                break;

            case 'boolean':
                if (! is_bool($value)) {
                    $this->fail($provider, "$path must be a boolean");
                }
                break;
        }
    }

    /**
     * @throws AIProviderException
     */
    protected function fail(string $provider, string $message): never
    {
        throw new AIProviderException($provider, "Structured output validation failed: $message");
    }
}
