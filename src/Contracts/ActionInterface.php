<?php

namespace Asterism\Actions\Contracts;

interface ActionInterface
{
    /**
     * Action handler
     * @return mixed|void
     */
    public function handle();
}
