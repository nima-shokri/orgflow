<?php

namespace Tests\Feature;

use App\Models\ProcessDefinition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OperatonIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_operaton_dashboard_and_see_runtime_definitions(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');
        config()->set('operaton.web_url', 'http://operaton.test/operaton');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Http::fake([
            'http://operaton.test/engine-rest/version' => Http::response([
                'version' => '1.0.0',
            ]),
            'http://operaton.test/engine-rest/process-definition*' => Http::response([
                [
                    'id' => 'invoice-approval:1:abc123',
                    'key' => 'invoice-approval',
                    'name' => 'Invoice Approval',
                    'version' => 1,
                    'deploymentId' => 'deployment-1',
                ],
            ]),
        ]);

        $response = $this->actingAs($admin)->get(route('operaton.dashboard'));

        $response->assertOk();
        $response->assertSee('Operaton engine');
        $response->assertSee('invoice-approval');
        $response->assertSee('1.0.0');
    }

    public function test_admin_can_deploy_a_published_definition_to_operaton(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');
        config()->set('operaton.default_history_ttl', 180);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $definition = ProcessDefinition::create([
            'process_key' => 'invoice-approval',
            'name' => 'Invoice Approval',
            'version' => 1,
            'status' => ProcessDefinition::STATUS_PUBLISHED,
            'bpmn_xml' => <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
                  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
                  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
                  xmlns:di="http://www.omg.org/spec/DD/20100524/DI"
                  id="Definitions_invoice-approval"
                  targetNamespace="http://bpms.local/bpmn">
  <bpmn:process id="invoice-approval" name="Invoice Approval" isExecutable="true">
    <bpmn:startEvent id="StartEvent_1" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="invoice-approval" />
  </bpmndi:BPMNDiagram>
</bpmn:definitions>
XML,
            'created_by' => $admin->id,
            'published_at' => now(),
        ]);

        Http::fake([
            'http://operaton.test/engine-rest/deployment/create' => Http::response([
                'id' => 'deployment-1',
                'name' => 'invoice-approval v1',
                'deployedProcessDefinitions' => [
                    'invoice-approval:1:abc123' => [
                        'id' => 'invoice-approval:1:abc123',
                        'key' => 'invoice-approval',
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('process-definitions.deploy', $definition));

        $response->assertRedirect(route('process-definitions.show', $definition));
        $response->assertSessionHas('status', 'Published definition deployed to Operaton successfully.');

        $definition->refresh();

        $this->assertSame('deployment-1', $definition->engine_deployment_id);
        $this->assertSame('invoice-approval:1:abc123', $definition->engine_process_definition_id);
        $this->assertNotNull($definition->engine_deployed_at);
        $this->assertNull($definition->engine_deployment_error);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://operaton.test/engine-rest/deployment/create';
        });
    }

    public function test_deploy_injects_default_history_ttl_when_model_does_not_define_it(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/engine-rest');
        config()->set('operaton.default_history_ttl', 180);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $definition = ProcessDefinition::create([
            'process_key' => 'invoice-approval',
            'name' => 'Invoice Approval',
            'version' => 2,
            'status' => ProcessDefinition::STATUS_PUBLISHED,
            'bpmn_xml' => <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
                  id="Definitions_invoice-approval"
                  targetNamespace="http://bpms.local/bpmn">
  <bpmn:process id="invoice-approval" name="Invoice Approval" isExecutable="true">
    <bpmn:startEvent id="StartEvent_1" />
  </bpmn:process>
</bpmn:definitions>
XML,
            'created_by' => $admin->id,
            'published_at' => now(),
        ]);

        Http::fake([
            'http://operaton.test/engine-rest/deployment/create' => Http::response([
                'id' => 'deployment-2',
                'name' => 'invoice-approval v2',
                'deployedProcessDefinitions' => [
                    'invoice-approval:2:def456' => [
                        'id' => 'invoice-approval:2:def456',
                        'key' => 'invoice-approval',
                    ],
                ],
            ]),
        ]);

        $this->actingAs($admin)->post(route('process-definitions.deploy', $definition));

        Http::assertSent(function ($request) {
            $body = $request->body();

            return $request->method() === 'POST'
                && $request->url() === 'http://operaton.test/engine-rest/deployment/create'
                && str_contains($body, 'camunda:historyTimeToLive="180"')
                && str_contains($body, 'xmlns:camunda="http://camunda.org/schema/1.0/bpmn"');
        });
    }

    public function test_operaton_client_falls_back_from_legacy_rest_path_to_engine_rest(): void
    {
        config()->set('operaton.base_url', 'http://operaton.test/operaton/engine-rest');
        config()->set('operaton.web_url', 'http://operaton.test/operaton');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Http::fake([
            'http://operaton.test/operaton/engine-rest/version' => Http::response('Not Found', 404),
            'http://operaton.test/engine-rest/version' => Http::response([
                'version' => '1.0.0',
            ]),
            'http://operaton.test/operaton/engine-rest/process-definition*' => Http::response('Not Found', 404),
            'http://operaton.test/engine-rest/process-definition*' => Http::response([]),
        ]);

        $response = $this->actingAs($admin)->get(route('operaton.dashboard'));

        $response->assertOk();
        $response->assertSee('UP');
        $response->assertSee('http://operaton.test/engine-rest');
        $response->assertSee('1.0.0');
    }
}
