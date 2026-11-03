<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Server-side validation.
 *
 *   $v = new Validator($_POST, [
 *       'name'  => 'required|max:100',
 *       'email' => 'required|email',
 *   ], ['name' => 'Nama']);
 *   if ($v->fails()) { ... $v->errors() ... }
 *
 * Supported rules: required, email, min:n, max:n (string length),
 * numeric, integer, min_value:n, max_value:n, in:a,b,c, phone, money, confirmed.
 */
final class Validator
{
    private array $errors = [];
    private array $clean = [];

    public function __construct(private array $input, array $rules, private array $labels = [])
    {
        foreach ($rules as $field => $ruleString) {
            $this->check($field, explode('|', $ruleString));
        }
    }

    private function check(string $field, array $rules): void
    {
        $raw = $this->input[$field] ?? '';
        $value = is_string($raw) ? trim($raw) : '';
        if (!is_string($raw)) {
            $this->errors[$field] = $this->label($field) . ' tidak sah.';
            return;
        }
        $label = $this->label($field);

        if ($value === '') {
            if (in_array('required', $rules, true)) {
                $this->errors[$field] = "$label diperlukan.";
            }
            $this->clean[$field] = '';
            return;
        }

        foreach ($rules as $rule) {
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
            $error = match ($name) {
                'required'  => null,
                'email'     => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "$label mesti alamat e-mel yang sah.",
                'min'       => mb_strlen($value) >= (int) $arg ? null : "$label mesti sekurang-kurangnya $arg aksara.",
                'max'       => mb_strlen($value) <= (int) $arg ? null : "$label tidak boleh melebihi $arg aksara.",
                'numeric'   => is_numeric($value) ? null : "$label mesti nombor.",
                'integer'   => preg_match('/^-?\d+$/', $value) ? null : "$label mesti nombor bulat.",
                'min_value' => (is_numeric($value) && $value >= (float) $arg) ? null : "$label mesti sekurang-kurangnya $arg.",
                'max_value' => (is_numeric($value) && $value <= (float) $arg) ? null : "$label tidak boleh melebihi $arg.",
                'in'        => in_array($value, explode(',', (string) $arg), true) ? null : "$label tidak sah.",
                'phone'     => preg_match('/^\+?[0-9][0-9 \-]{7,18}$/', $value) ? null : "$label mesti nombor telefon yang sah (contoh: 012-345 6789).",
                'money'     => preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value) ? null : "$label mesti jumlah wang yang sah (contoh: 5.50).",
                'confirmed' => ($value === trim((string) ($this->input[$field . '_confirmation'] ?? ''))) ? null : "Pengesahan $label tidak sepadan.",
                default     => throw new \LogicException("Unknown rule $name"),
            };
            if ($error !== null) {
                $this->errors[$field] = $error;
                return;
            }
        }
        $this->clean[$field] = $value;
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    /** Trimmed, validated values. */
    public function validated(): array
    {
        return $this->clean;
    }
}
