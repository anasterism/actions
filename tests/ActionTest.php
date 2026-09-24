<?php

namespace Asterism\Actions\Tests;

use Asterism\Actions\Action;
use Asterism\Actions\Jobs\ActionJob;
use Asterism\Actions\Tests\Fixtures\TestAction;
use Asterism\Actions\Tests\Fixtures\TestUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;

class ActionTest extends TestCase
{
    public function test_execute_validates_and_runs_handle(): void
    {
        $result = TestAction::execute(name: 'widget');

        $this->assertSame('widget', $result);
    }

    public function test_execute_accepts_zero_arguments_without_the_misuse_guard_firing(): void
    {
        // array_is_list([]) === true in PHP - prepareArguments() must guard
        // with !empty($arguments) first, or every zero-argument Action call
        // would incorrectly throw. Regression test for a bug already found
        // and fixed once in the app copy of this class.
        $action = new class extends \Asterism\Actions\Action {
            public function handle(): mixed
            {
                return 'ran';
            }
        };

        $result = (new \Asterism\Actions\ActionBuilder($action))->execute();

        $this->assertSame('ran', $result);
    }

    public function test_invalid_arguments_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TestAction::execute(name: 123);
    }

    public function test_actor_is_populated_from_the_authenticated_user_at_construction(): void
    {
        $user = TestUser::create([
            'name'     => 'Ada Lovelace',
            'email'    => 'ada@example.com',
            'password' => 'secret',
        ]);

        $this->actingAs($user);

        $action = new TestAction();

        $this->assertTrue($action->actor->is($user));
    }

    public function test_actor_survives_serialization_and_is_requeried_fresh(): void
    {
        $user = TestUser::create([
            'name'     => 'Ada Lovelace',
            'email'    => 'ada@example.com',
            'password' => 'secret',
        ]);

        $this->actingAs($user);

        $action = new TestAction();
        $action->arguments = $action->arguments->merge(['name' => 'widget']);

        $serialized = serialize($action);
        $user->update(['name' => 'Ada King']);

        /** @var TestAction $restored */
        $restored = unserialize($serialized);

        $this->assertInstanceOf(TestUser::class, $restored->actor);
        $this->assertTrue($restored->actor->is($user));
        $this->assertSame('Ada King', $restored->actor->name);
        $this->assertSame('widget', $restored->arg('name'));
    }

    public function test_actor_survives_serialization_when_null(): void
    {
        $action = new TestAction();
        $action->arguments = $action->arguments->merge(['name' => 'widget']);

        $restored = unserialize(serialize($action));

        $this->assertNull($restored->actor);
        $this->assertSame('widget', $restored->arg('name'));
    }

    public function test_a_void_handler_is_supported(): void
    {
        $action = new class extends Action {
            public bool $ran = false;

            public function handle(): void
            {
                $this->ran = true;
            }
        };

        $result = (new \Asterism\Actions\ActionBuilder($action))->execute();

        $this->assertNull($result);
        $this->assertTrue($action->ran);
    }

    public function test_dispatch_queues_the_action_with_its_arguments(): void
    {
        Queue::fake();

        TestAction::dispatch(name: 'widget');

        Queue::assertPushed(ActionJob::class, 1);
    }

    public function test_dispatch_rejects_invalid_arguments_before_queueing(): void
    {
        Queue::fake();

        try {
            TestAction::dispatch(name: 123);
            $this->fail('Expected InvalidArgumentException.');
        } catch (InvalidArgumentException) {
            Queue::assertNothingPushed();
        }
    }

    public function test_dispatch_checks_authorization_before_queueing(): void
    {
        Queue::fake();

        $action = new class extends Action {
            protected function authorize(): bool
            {
                return false;
            }

            public function handle(): void
            {
            }
        };

        try {
            (new \Asterism\Actions\ActionBuilder($action))->dispatch();
            $this->fail('Expected AuthorizationException.');
        } catch (AuthorizationException) {
            Queue::assertNothingPushed();
        }
    }
}
