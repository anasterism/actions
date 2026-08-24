<?php

namespace Asterism\Actions\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeActionCommand extends GeneratorCommand
{
    protected $name = 'make:action';
    protected $description = 'Create a new action class.';
    protected $type = 'Action';

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Actions';
    }

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/action.stub');
    }

    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . '/../' . ltrim($stub, '/');
    }
}
