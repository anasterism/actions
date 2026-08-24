<?php

namespace Asterism\Actions\Validation;

use Illuminate\Validation\ValidationException;

class ActionValidationException extends ValidationException
{
    public function __construct($validator)
    {
        parent::__construct($validator);
    }

    public function summary(): string
    {
        return static::summarize($this->validator);
    }

    protected static function summarize($validator): string
    {
        $messages = collect($validator->errors()->all())
            ->map(fn ($m) => str_replace(' field', ' argument', $m))
            ->all();

        if (!count($messages)) {
            return 'The given arguments were invalid.';
        }

        return implode(' ', $messages);
    }
}
