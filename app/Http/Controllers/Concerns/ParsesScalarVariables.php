<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Validation\ValidationException;
use JsonException;

trait ParsesScalarVariables
{
    /**
     * @return array<string, bool|int|float|string|null>
     */
    protected function parseScalarVariablesJson(
        ?string $variablesJson,
        string $field = 'variables_json',
        string $label = 'Variables',
    ): array {
        $variablesJson = trim((string) $variablesJson);

        if ($variablesJson === '') {
            return [];
        }

        try {
            $decoded = json_decode($variablesJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                $field => "$label must be valid JSON.",
            ]);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw ValidationException::withMessages([
                $field => "$label must be a JSON object like {\"approved\": true}.",
            ]);
        }

        foreach ($decoded as $name => $value) {
            if (! is_string($name) || $name === '') {
                throw ValidationException::withMessages([
                    $field => "The values inside $label must use non-empty string keys.",
                ]);
            }

            if (is_array($value) || is_object($value)) {
                throw ValidationException::withMessages([
                    $field => "Only scalar values or null are supported in $label.",
                ]);
            }
        }

        return $decoded;
    }
}
