<?php

namespace Asterism\Actions;

use Asterism\Actions\Contracts\ActionInterface;
use Asterism\Actions\Validation\ActionValidationException;
use BadMethodCallException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Queue\SerializesAndRestoresModelIdentifiers;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use OutOfBoundsException;

/**
 * @method static ActionBuilder when($condition, $callback)
 * @method static ActionBuilder unless($condition, $callback)
 * @method static ActionBuilder actingAs(Authenticatable $user)
 * @method static ActionBuilder enableBenchmark()
 * @method static mixed execute(mixed ...$arguments)
 * @method static void dispatch(mixed ...$arguments)
 */
abstract class Action implements ActionInterface
{
    use SerializesAndRestoresModelIdentifiers;

    public ?Authenticatable $actor = null;

    public Collection $arguments;

    public function __construct()
    {
        $this->actor = auth()->user();
        $this->arguments = new Collection();
    }

    public function __serialize(): array
    {
        return [
            'actor'     => $this->getSerializedPropertyValue($this->actor),
            'arguments' => $this->arguments,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->actor     = $this->getRestoredPropertyValue($data['actor']);
        $this->arguments = $data['arguments'];
    }

    public function __get(string $name): mixed
    {
        if (!$this->arguments->has($name)) {
            throw new OutOfBoundsException(sprintf('Undefined property: %s::$%s', static::class, $name));
        }

        return $this->arguments->get($name);
    }

    public function arg(string $name, mixed $default = null): mixed
    {
        return $this->arguments->get($name) ?? $default;
    }

    public function arguments(): Collection
    {
        return $this->arguments;
    }

    protected function authorize(): bool
    {
        return true;
    }

    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    public function checkAuthorization(): void
    {
        if (!$this->authorize()) {
            throw new AuthorizationException();
        }
    }

    public function validate(): void
    {
        $validator = Validator::make($this->arguments->toArray(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            $ex = new ActionValidationException($validator);

            if (app()->runningInConsole() || app()->runningUnitTests()) {
                throw new InvalidArgumentException($ex->summary(), 0);
            } else {
                throw $ex;
            }
        }
    }

    /**
     * Magic static call to bootstrap the builder fluent chain.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        $actionInstance = app(static::class);
        $builder = new ActionBuilder($actionInstance);

        if (!method_exists($builder, $method)) {
            throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $method));
        }

        return $builder->$method(...$arguments);
    }

}
