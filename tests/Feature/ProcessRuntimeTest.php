<?php

namespace Tests\Feature;

use App\Models\ProcessDefinition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_start_a_deployed_published_process_definition(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $definition = $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/process-definition/invoice-approval%3A1%3Aabc123/start' => Http::response([
                'id' => 'instance-1',
                'definitionId' => 'invoice-approval:1:abc123',
                'businessKey' => 'invoice-approval-demo-001',
                'ended' => false,
                'suspended' => false,
            ]),
        ]);

        $response = $this->actingAs($admin)->post(route('process-definitions.start', $definition), [
            'business_key' => 'invoice-approval-demo-001',
        ]);

        $response->assertRedirect(route('runtime.instances.show', 'instance-1'));
        $response->assertSessionHas('status', 'Process instance started successfully.');

        Http::assertSent(function ($request) {
            $payload = json_decode($request->body(), true);

            return $request->method() === 'POST'
                && $request->url() === 'http://operaton.test/engine-rest/process-definition/invoice-approval%3A1%3Aabc123/start'
                && ($payload['businessKey'] ?? null) === 'invoice-approval-demo-001';
        });
    }

    public function test_admin_can_open_runtime_explorer_and_see_recent_instances(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');
        config()->set('operaton.web_url', 'http://operaton.test/operaton');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $definition = $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/version' => Http::response([
                'version' => '1.0.0',
            ]),
            'http://operaton.test/engine-rest/history/process-instance*' => Http::response([
                [
                    'id' => 'instance-1',
                    'processDefinitionId' => 'invoice-approval:1:abc123',
                    'processDefinitionKey' => 'invoice-approval',
                    'processDefinitionName' => 'Invoice Approval',
                    'businessKey' => 'bk-1',
                    'startTime' => '2026-07-05T08:00:00.000+0000',
                    'state' => 'ACTIVE',
                ],
            ]),
        ]);

        $response = $this->actingAs($admin)->get(route('runtime.instances.index', [
            'definition' => $definition->engine_process_definition_id,
        ]));

        $response->assertOk();
        $response->assertSee('Runtime explorer');
        $response->assertSee('invoice-approval');
        $response->assertSee('bk-1');
        $response->assertSee('ACTIVE');
    }

    public function test_admin_can_start_process_without_business_key(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $definition = $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/process-definition/invoice-approval%3A1%3Aabc123/start' => Http::response([
                'id' => 'instance-2',
                'definitionId' => 'invoice-approval:1:abc123',
                'ended' => false,
                'suspended' => false,
            ]),
        ]);

        $response = $this->actingAs($admin)->post(route('process-definitions.start', $definition), []);

        $response->assertRedirect(route('runtime.instances.show', 'instance-2'));
        $response->assertSessionHas('status', 'Process instance started successfully.');

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://operaton.test/engine-rest/process-definition/invoice-approval%3A1%3Aabc123/start'
                && trim($request->body()) === '{}';
        });
    }

    public function test_admin_can_open_process_instance_detail_and_see_active_tasks(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/history/process-instance/instance-1' => Http::response([
                'id' => 'instance-1',
                'processDefinitionId' => 'invoice-approval:1:abc123',
                'processDefinitionKey' => 'invoice-approval',
                'processDefinitionName' => 'Invoice Approval',
                'processDefinitionVersion' => 1,
                'businessKey' => 'bk-42',
                'startTime' => '2026-07-05T08:00:00.000+0000',
                'endTime' => null,
                'durationInMillis' => 1500,
                'state' => 'ACTIVE',
                'startActivityId' => 'StartEvent_1',
                'rootProcessInstanceId' => 'instance-1',
                'deleteReason' => null,
            ]),
            'http://operaton.test/engine-rest/process-instance/instance-1' => Http::response([
                'id' => 'instance-1',
                'definitionId' => 'invoice-approval:1:abc123',
                'businessKey' => 'bk-42',
                'ended' => false,
                'suspended' => false,
            ]),
            'http://operaton.test/engine-rest/task*' => Http::response([
                [
                    'id' => 'task-1',
                    'name' => 'Review request',
                    'assignee' => null,
                    'created' => '2026-07-05T08:00:05.000+0000',
                ],
            ]),
            'http://operaton.test/engine-rest/history/variable-instance*' => Http::response([
                [
                    'id' => 'var-1',
                    'name' => 'approved',
                    'type' => 'Boolean',
                    'value' => true,
                    'processInstanceId' => 'instance-1',
                    'activityInstanceId' => 'Activity_Review:1',
                    'createTime' => '2026-07-05T08:01:00.000+0000',
                ],
                [
                    'id' => 'var-2',
                    'name' => 'amount',
                    'type' => 'Integer',
                    'value' => 7,
                    'processInstanceId' => 'instance-1',
                    'activityInstanceId' => 'Activity_Review:1',
                    'createTime' => '2026-07-05T08:01:10.000+0000',
                ],
            ]),
            'http://operaton.test/engine-rest/history/activity-instance*' => Http::response([
                [
                    'id' => 'activity-1',
                    'activityId' => 'StartEvent_1',
                    'activityName' => 'Start',
                    'activityType' => 'startEvent',
                    'processInstanceId' => 'instance-1',
                    'startTime' => '2026-07-05T08:00:00.000+0000',
                    'endTime' => '2026-07-05T08:00:00.200+0000',
                    'durationInMillis' => 200,
                ],
                [
                    'id' => 'activity-2',
                    'activityId' => 'Activity_Review',
                    'activityName' => 'Review request',
                    'activityType' => 'userTask',
                    'processInstanceId' => 'instance-1',
                    'startTime' => '2026-07-05T08:00:05.000+0000',
                    'endTime' => null,
                    'durationInMillis' => null,
                    'canceled' => false,
                ],
            ]),
        ]);

        $response = $this->actingAs($admin)->get(route('runtime.instances.show', 'instance-1'));

        $response->assertOk();
        $response->assertSee('Process instance');
        $response->assertSee('invoice-approval:1:abc123');
        $response->assertSee('bk-42');
        $response->assertSee('Review request');
        $response->assertSee('StartEvent_1');
        $response->assertSee('Process variables');
        $response->assertSee('approved');
        $response->assertSee('true');
        $response->assertSee('amount');
        $response->assertSee('Activity timeline');
        $response->assertSee('startEvent');
        $response->assertSee('Running');
    }

    public function test_runtime_detail_still_loads_when_history_panels_fail(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->createDeployedDefinition($admin);

        Http::fake([
            'http://operaton.test/engine-rest/history/process-instance/instance-1' => Http::response([
                'id' => 'instance-1',
                'processDefinitionId' => 'invoice-approval:1:abc123',
                'processDefinitionKey' => 'invoice-approval',
                'processDefinitionName' => 'Invoice Approval',
                'processDefinitionVersion' => 1,
                'businessKey' => 'bk-42',
                'startTime' => '2026-07-05T08:00:00.000+0000',
                'state' => 'ACTIVE',
            ]),
            'http://operaton.test/engine-rest/process-instance/instance-1' => Http::response([
                'id' => 'instance-1',
                'definitionId' => 'invoice-approval:1:abc123',
                'businessKey' => 'bk-42',
                'ended' => false,
                'suspended' => false,
            ]),
            'http://operaton.test/engine-rest/task*' => Http::response([]),
            'http://operaton.test/engine-rest/history/variable-instance*' => Http::response([
                'message' => 'history variables unavailable',
            ], 500),
            'http://operaton.test/engine-rest/history/activity-instance*' => Http::response([
                'message' => 'activity history unavailable',
            ], 500),
        ]);

        $response = $this->actingAs($admin)->get(route('runtime.instances.show', 'instance-1'));

        $response->assertOk();
        $response->assertSee('Process instance');
        $response->assertSee('Could not load process variables from Operaton');
        $response->assertSee('history variables unavailable');
        $response->assertSee('Could not load activity history from Operaton');
        $response->assertSee('activity history unavailable');
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
