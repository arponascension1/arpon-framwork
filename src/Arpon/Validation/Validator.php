<?php

namespace Arpon\Validation;
use Arpon\Validation\Rules\StringType;
use Arpon\Validation\Rules\NumericType; // Added
use InvalidArgumentException;

class Validator
{
    protected array $data;
    protected array $rules;
    protected array $messages = [];
    protected ErrorBag $errorBag;

    protected array $ruleMap = [
        'string' => StringType::class,
        'numeric' => NumericType::class, // Added
    ];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
        $this->errorBag = new ErrorBag();
    }

    public static function make(array $data, array $rules, array $messages = []): static
    {
        return new static($data, $rules, $messages);
    }

    public function validate(): array
    {
        foreach ($this->rules as $attribute => $rules) {
            $rules = is_string($rules) ? explode('|', $rules) : (array) $rules;

            if (in_array('nullable', $rules)) {
                $value = $this->data[$attribute] ?? null;
                if (is_null($value) || ($value instanceof \Arpon\Http\File\UploadedFile && $value->getError() === UPLOAD_ERR_NO_FILE)) {
                    continue;
                }
            }

            foreach ($rules as $rule) {
                $this->validateAttribute($attribute, $rule);
            }
        }

        if ($this->errorBag->hasErrors()) {
            throw new ValidationException($this->errorBag);
        }

        return $this->data;
    }

    protected function validateAttribute(string $attribute, string $ruleString): void
    {
        $value = $this->data[$attribute] ?? null;

        // If the attribute is a file, use the UploadedFile instance
        if (isset($this->data[$attribute]) && $this->data[$attribute] instanceof \Arpon\Http\File\UploadedFile) {
            $value = $this->data[$attribute];
        }

        [$ruleName, $parameters] = $this->parseRule($ruleString);

        if (isset($this->ruleMap[$ruleName])) {
            $ruleClass = $this->ruleMap[$ruleName];
        } else {
            // Attempt to convert snake_case to PascalCase for class name
            $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $ruleName)));
            $ruleClass = 'Arpon\\Validation\\Rules\\'.$className;
        }

        if (!class_exists($ruleClass)) {
            throw new InvalidArgumentException("Validation rule [{$ruleName}] not found.");
        }

        $ruleInstance = new $ruleClass();

        if (!$ruleInstance->validate($attribute, $value, $parameters, $this->data)) {
            $customMessage = $this->messages["{$attribute}.{$ruleName}"] ?? $this->messages[$ruleName] ?? null;

            $formattedAttribute = str_replace('_', ' ', $attribute);
            $formattedAttribute = ucfirst($formattedAttribute);

            if ($customMessage) {
                $message = str_replace([':attribute', ':rule'], [$formattedAttribute, $ruleName], $customMessage);
            } else {
                $message = $ruleInstance->message($attribute, $value, $parameters, $this->data);
                $message = str_replace(':attribute', $formattedAttribute, $message);
            }
            $this->errorBag->add($attribute, $message);
        }
    }

    protected function parseRule(string $ruleString): array
    {
        $parts = explode(':', $ruleString, 2);
        $ruleName = $parts[0];
        $parameters = isset($parts[1]) ? explode(',', $parts[1]) : [];

        return [$ruleName, $parameters];
    }

    public function errors(): ErrorBag
    {
        return $this->errorBag;
    }
}