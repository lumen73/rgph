<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

/**
 * Validation de données déclarative.
 *
 * Exemple :
 *   $validator = Validator::make($data, [
 *       'nom'        => 'required|minlength:2|maxlength:250',
 *       'population' => 'required|integer|min:0',
 *       'idcommune'  => 'required|integer|unique:commune,idcommune',
 *   ]);
 */
final class Validator
{
    /** @var array<string, mixed> */
    private $data;

    /** @var array<string, string> */
    private $rules;

    /** @var array<string, array<int, string>> */
    private $errors = [];

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules
     */
    public function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;

        $this->validate();
    }

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules
     */
    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            return $messages[0];
        }

        return null;
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;

            foreach (explode('|', $ruleString) as $rule) {
                $parts     = explode(':', $rule, 2);
                $name      = $parts[0];
                $parameter = $parts[1] ?? null;

                if ($name === '') {
                    continue;
                }

                $this->applyRule($field, $value, $name, $parameter);
            }
        }
    }

    /**
     * @param mixed $value
     */
    private function applyRule(string $field, $value, string $rule, ?string $parameter): void
    {
        $empty = $value === null || $value === '' || $value === [];

        switch ($rule) {
            case 'required':
                if ($empty) {
                    $this->add($field, 'Le champ « ' . $field . ' » est obligatoire.');
                }
                break;

            case 'email':
                if (!$empty && filter_var((string) $value, FILTER_VALIDATE_EMAIL) === false) {
                    $this->add($field, 'L\'adresse e-mail n\'est pas valide.');
                }
                break;

            case 'integer':
                if (!$empty && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->add($field, 'Le champ « ' . $field . ' » doit être un nombre entier.');
                }
                break;

            case 'numeric':
                if (!$empty && !is_numeric($value)) {
                    $this->add($field, 'Le champ « ' . $field . ' » doit être numérique.');
                }
                break;

            case 'min':
                if (!$empty && is_numeric($value) && (float) $value < (float) $parameter) {
                    $this->add($field, 'Le champ « ' . $field . ' » doit être supérieur ou égal à ' . $parameter . '.');
                }
                break;

            case 'max':
                if (!$empty && is_numeric($value) && (float) $value > (float) $parameter) {
                    $this->add($field, 'Le champ « ' . $field . ' » ne peut pas dépasser ' . $parameter . '.');
                }
                break;

            case 'minlength':
                if (!$empty && mb_strlen((string) $value) < (int) $parameter) {
                    $this->add($field, 'Le champ « ' . $field . ' » doit contenir au moins ' . $parameter . ' caractères.');
                }
                break;

            case 'maxlength':
                if (!$empty && mb_strlen((string) $value) > (int) $parameter) {
                    $this->add($field, 'Le champ « ' . $field . ' » ne peut pas dépasser ' . $parameter . ' caractères.');
                }
                break;

            case 'in':
                $allowed = explode(',', (string) $parameter);
                if (!$empty && !in_array((string) $value, $allowed, true)) {
                    $this->add($field, 'La valeur du champ « ' . $field . ' » est invalide.');
                }
                break;

            case 'same':
                if ((string) $value !== (string) ($this->data[$parameter] ?? '')) {
                    $this->add($field, 'Le champ « ' . $field . ' » ne correspond pas à « ' . $parameter . ' ».');
                }
                break;

            case 'unique':
                if (!$empty) {
                    $this->validateUnique($field, $value, (string) $parameter);
                }
                break;

            case 'exists':
                if (!$empty) {
                    $this->validateExists($field, $value, (string) $parameter);
                }
                break;
        }
    }

    /**
     * Format du paramètre : table,colonne
     *
     * @param mixed $value
     */
    private function validateExists(string $field, $value, string $parameter): void
    {
        $parts  = explode(',', $parameter);
        $table  = $parts[0] ?? '';
        $column = $parts[1] ?? $field;

        $count = (int) Database::scalar(
            'SELECT COUNT(*) FROM ' . Database::quote($table)
            . ' WHERE ' . Database::quote($column) . ' = :value',
            ['value' => $value]
        );

        if ($count === 0) {
            $this->add($field, 'La valeur sélectionnée pour « ' . $field . ' » est invalide.');
        }
    }

    /**
     * Format du paramètre : table,colonne[,idÀIgnorer,colonneClé]
     *
     * @param mixed $value
     */
    private function validateUnique(string $field, $value, string $parameter): void
    {
        $parts    = explode(',', $parameter);
        $table    = $parts[0] ?? '';
        $column   = $parts[1] ?? $field;
        $ignoreId = $parts[2] ?? null;
        $key      = $parts[3] ?? 'id';

        $sql    = 'SELECT COUNT(*) FROM ' . Database::quote($table)
            . ' WHERE ' . Database::quote($column) . ' = :value';
        $params = ['value' => $value];

        if ($ignoreId !== null && $ignoreId !== '') {
            $sql .= ' AND ' . Database::quote($key) . ' <> :ignore';
            $params['ignore'] = (int) $ignoreId;
        }

        if ((int) Database::scalar($sql, $params) > 0) {
            $this->add($field, 'Cette valeur est déjà utilisée pour « ' . $field . ' ».');
        }
    }

    private function add(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}