<?php

namespace Asterism\Actions\Tests\Fixtures;

use Asterism\Actions\Action;

class TestAction extends Action
{
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }

    public function handle(): mixed
    {
        return $this->arg('name');
    }
}
