<?php

namespace Asterism\Actions\Tests\Validation\Rules;

use Asterism\Actions\Tests\TestCase;
use Asterism\Actions\Validation\Rules\TypeOf;
use Illuminate\Support\Facades\Validator;

class TypeOfTest extends TestCase
{
    // Regression guard for the exact mistake made while writing this rule:
    // a bare __invoke() with no interface silently doesn't work as a
    // validation rule at all in Laravel 9 (ValidationRuleParser::prepareRule()
    // only takes the invokable path for objects instanceof InvokableRule,
    // otherwise falls through to a (string) cast and throws). Running this
    // through the real Validator, not just calling __invoke() directly, is
    // the point - it proves Laravel actually recognizes the rule.
    public function test_matching_primitive_type_passes(): void
    {
        $validator = Validator::make(['n' => 5], ['n' => [new TypeOf('int')]]);

        $this->assertTrue($validator->passes());
    }

    public function test_mismatched_primitive_type_fails_with_a_message(): void
    {
        $validator = Validator::make(['n' => 'five'], ['n' => [new TypeOf('int')]]);

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('must be of type int', $validator->errors()->first('n'));
    }

    public function test_instanceof_check_passes_for_a_matching_class(): void
    {
        $validator = Validator::make(
            ['obj' => new \stdClass()],
            ['obj' => [new TypeOf(\stdClass::class)]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_instanceof_check_fails_for_a_non_matching_class(): void
    {
        $validator = Validator::make(
            ['obj' => new \stdClass()],
            ['obj' => [new TypeOf(\Asterism\Actions\Action::class)]]
        );

        $this->assertTrue($validator->fails());
    }
}
