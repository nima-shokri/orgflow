<?php

namespace Tests\Feature;

use App\Models\ProcessDefinition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaskInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_task_inbox_and_see_active_tasks(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/version' => Http::response([
                'version' => '1.0.0',
            ]),
            'http://operaton.test/engine-rest/task*' => Http::response([
                [
                    'id' => 'task-1',
                    'name' => 'Review request',
                    'taskDefinitionKey' => 'Activity_Review',
                    'processInstanceId' => 'instance-1',
                    'processDefinitionId' => 'invoice-approval:1:abc123',
                    'assignee' => null,
                    'created' => '2026-07-05T08:00:05.000+0000',
                ],
            ]),
        ]);

        $response = $this->actingAs($admin)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertSee('Task inbox');
        $response->assertSee('Review request');
        $response->assertSee('invoice-approval');
        $response->assertSee('instance-1');
    }

    public function test_admin_can_open_task_detail(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/task/task-1' => Http::response([
                'id' => 'task-1',
                'name' => 'Review request',
                'taskDefinitionKey' => 'Activity_Review',
                'processInstanceId' => 'instance-1',
                'processDefinitionId' => 'invoice-approval:1:abc123',
                'executionId' => 'execution-1',
                'priority' => 50,
                'assignee' => null,
                'created' => '2026-07-05T08:00:05.000+0000',
            ]),
            'http://operaton.test/engine-rest/task/task-1/form-variables*' => Http::response([
                'approved' => [
                    'type' => 'Boolean',
                    'value' => true,
                    'valueInfo' => [],
                ],
            ]),
        ]);

        $response = $this->actingAs($admin)->get(route('tasks.show', 'task-1'));

        $response->assertOk();
        $response->assertSee('Review request');
        $response->assertSee('Activity_Review');
        $response->assertSee('invoice-approval');
        $response->assertSee('instance-1');
        $response->assertSee('approved');
        $response->assertSee('Boolean');
    }

    public function test_admin_can_complete_task_and_redirect_back_to_runtime_instance(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/task/task-1' => Http::response([
                'id' => 'task-1',
                'name' => 'Review request',
                'processInstanceId' => 'instance-1',
                'processDefinitionId' => 'invoice-approval:1:abc123',
            ]),
            'http://operaton.test/engine-rest/task/task-1/complete' => Http::response('', 204),
        ]);

        $response = $this->actingAs($admin)->post(route('tasks.complete', 'task-1'), [
            'variables_json' => '{"approved":true,"comment":"ok","amount":7}',
        ]);

        $response->assertRedirect(route('runtime.instances.show', 'instance-1'));
        $response->assertSessionHas('status', 'Task completed successfully.');

        Http::assertSent(function ($request) {
            if ($request->method() !== 'POST' || $request->url() !== 'http://operaton.test/engine-rest/task/task-1/complete') {
                return false;
            }

            $payload = json_decode($request->body(), true);

            return ($payload['variables']['approved']['value'] ?? null) === true
                && ($payload['variables']['approved']['type'] ?? null) === 'Boolean'
                && ($payload['variables']['comment']['value'] ?? null) === 'ok'
                && ($payload['variables']['comment']['type'] ?? null) === 'String'
                && ($payload['variables']['amount']['value'] ?? null) === 7
                && ($payload['variables']['amount']['type'] ?? null) === 'Integer';
        });
    }

    public function test_admin_can_submit_generated_task_form_values(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/task/task-1' => Http::response([
                'id' => 'task-1',
                'name' => 'Review request',
                'processInstanceId' => 'instance-1',
                'processDefinitionId' => 'invoice-approval:1:abc123',
            ]),
            'http://operaton.test/engine-rest/task/task-1/form-variables*' => Http::response([
                'approved' => [
                    'type' => 'Boolean',
                    'value' => false,
                    'valueInfo' => [],
                ],
                'comment' => [
                    'type' => 'String',
                    'value' => null,
                    'valueInfo' => [],
                ],
                'amount' => [
                    'type' => 'Integer',
                    'value' => 1,
                    'valueInfo' => [],
                ],
            ]),
            'http://operaton.test/engine-rest/task/task-1/submit-form' => Http::response('', 204),
        ]);

        $response = $this->actingAs($admin)->post(route('tasks.complete', 'task-1'), [
            'form_values' => [
                'approved' => 'true',
                'comment' => 'Approved from form',
                'amount' => '12',
            ],
            'variables_json' => '',
        ]);

        $response->assertRedirect(route('runtime.instances.show', 'instance-1'));
        $response->assertSessionHas('status', 'Task completed successfully.');

        Http::assertSent(function ($request) {
            if ($request->method() !== 'POST' || $request->url() !== 'http://operaton.test/engine-rest/task/task-1/submit-form') {
                return false;
            }

            $payload = json_decode($request->body(), true);

            return ($payload['variables']['approved']['value'] ?? null) === true
                && ($payload['variables']['approved']['type'] ?? null) === 'Boolean'
                && ($payload['variables']['comment']['value'] ?? null) === 'Approved from form'
                && ($payload['variables']['comment']['type'] ?? null) === 'String'
                && ($payload['variables']['amount']['value'] ?? null) === 12
                && ($payload['variables']['amount']['type'] ?? null) === 'Integer';
        });
    }

    public function test_admin_can_claim_an_unassigned_task(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'owner@bpms.test',
        ]);

        $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/task/task-1' => Http::response([
                'id' => 'task-1',
                'name' => 'Review request',
                'assignee' => null,
                'processInstanceId' => 'instance-1',
            ]),
            'http://operaton.test/engine-rest/task/task-1/claim' => Http::response('', 204),
        ]);

        $response = $this->from(route('tasks.index'))
            ->actingAs($admin)
            ->post(route('tasks.claim', 'task-1'));

        $response->assertRedirect(route('tasks.index'));
        $response->assertSessionHas('status', 'Task claimed successfully.');

        Http::assertSent(function ($request) {
            if ($request->method() !== 'POST' || $request->url() !== 'http://operaton.test/engine-rest/task/task-1/claim') {
                return false;
            }

            $payload = json_decode($request->body(), true);

            return ($payload['userId'] ?? null) === 'owner@bpms.test';
        });
    }

    public function test_admin_can_release_own_task(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'owner@bpms.test',
        ]);

        $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/task/task-1' => Http::response([
                'id' => 'task-1',
                'name' => 'Review request',
                'assignee' => 'owner@bpms.test',
                'processInstanceId' => 'instance-1',
            ]),
            'http://operaton.test/engine-rest/task/task-1/unclaim' => Http::response('', 204),
        ]);

        $response = $this->from(route('tasks.show', 'task-1'))
            ->actingAs($admin)
            ->post(route('tasks.release', 'task-1'));

        $response->assertRedirect(route('tasks.show', 'task-1'));
        $response->assertSessionHas('status', 'Task released successfully.');

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://operaton.test/engine-rest/task/task-1/unclaim';
        });
    }

    public function test_mine_scope_filters_task_inbox_to_current_users_tasks(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');
        config()->set('operaton.web_url', 'http://operaton.test/operaton');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'owner@bpms.test',
        ]);

        $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/version' => Http::response([
                'version' => '1.0.0',
            ]),
            'http://operaton.test/engine-rest/task*' => Http::response([
                [
                    'id' => 'task-1',
                    'name' => 'My task',
                    'taskDefinitionKey' => 'Activity_My',
                    'processInstanceId' => 'instance-1',
                    'processDefinitionId' => 'invoice-approval:1:abc123',
                    'assignee' => 'owner@bpms.test',
                    'created' => '2026-07-05T08:00:05.000+0000',
                ],
                [
                    'id' => 'task-2',
                    'name' => 'Other task',
                    'taskDefinitionKey' => 'Activity_Other',
                    'processInstanceId' => 'instance-2',
                    'processDefinitionId' => 'invoice-approval:1:abc123',
                    'assignee' => 'other@bpms.test',
                    'created' => '2026-07-05T08:05:05.000+0000',
                ],
            ]),
        ]);

        $response = $this->actingAs($admin)->get(route('tasks.index', [
            'scope' => 'mine',
        ]));

        $response->assertOk();
        $response->assertSee('My task');
        $response->assertDontSee('Other task');
        $response->assertSee('owner@bpms.test');
    }

    public function test_admin_cannot_complete_task_owned_by_another_user(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'owner@bpms.test',
        ]);

        $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/task/task-1' => Http::response([
                'id' => 'task-1',
                'name' => 'Review request',
                'assignee' => 'other@bpms.test',
                'processInstanceId' => 'instance-1',
                'processDefinitionId' => 'invoice-approval:1:abc123',
            ]),
        ]);

        $response = $this->from(route('tasks.show', 'task-1'))
            ->actingAs($admin)
            ->post(route('tasks.complete', 'task-1'), [
                'variables_json' => '{"approved":true}',
            ]);

        $response->assertRedirect(route('tasks.show', 'task-1'));
        $response->assertSessionHasErrors(['task']);

        Http::assertNotSent(function ($request) {
            return $request->url() === 'http://operaton.test/engine-rest/task/task-1/complete';
        });
    }

    private function createDeployedDefinition(User $admin): ProcessDefinition
    {
        return ProcessDefinition::create([
            'process_key' => 'invoice-approval',
            'name' => 'Invoice Approval',
            'version' => 1,
            'status' => ProcessDefinition::STATUS_PUBLISHED,
            'bpmn_xml' => '<definitions><process id="invoice-approval" /></definitions>',
            'created_by' => $admin->id,
            'published_at' => now(),
            'engine_deployment_id' => 'deployment-1',
            'engine_process_definition_id' => 'invoice-approval:1:abc123',
            'engine_deployed_at' => now(),
        ]);
    }
}
