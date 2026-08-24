<?php

namespace Asterism\Actions\Validation\Rules;

use Illuminate\Contracts\Validation\InvokableRule;

// InvokableRule (not the old Rule or the Laravel-10+ ValidationRule) - it's
// been stable since Laravel 8.42 with no interface churn, unlike Rule
// (passes()/message(), deprecated in 10) vs ValidationRule (validate(),
// added in 10). ValidationRuleParser::prepareRule() only takes the
// invokable path for objects that actually implement this interface; a
// plain __invoke() with no interface falls through to (string) casting
// and throws.
class TypeOf implements InvokableRule
{
    public function __construct(protected string $type) {}

    public function __invoke($attribute, $value, $fail): void
    {
        $passes = match ($this->type) {
            'int', 'integer'  => is_int($value),
            'string'          => is_string($value),
            'bool', 'boolean' => is_bool($value),
            'float', 'double' => is_float($value),
            'array'           => is_array($value),
            'object'          => is_object($value),
            'null'            => is_null($value),
            default           => $value instanceof $this->type,
        };

        if (! $passes) {
            $fail("The :attribute must be of type {$this->type}.");
        }
    }
}
