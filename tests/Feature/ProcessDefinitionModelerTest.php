<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessDefinitionModelerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_create_page_contains_renderable_bpmn_starter_xml(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get(route('process-definitions.create'));

        $response->assertOk();
        $response->assertSee('data-bpmn-modeler', false);
        $response->assertSee('bpmndi:BPMNDiagram', false);
        $response->assertSee('BPMNPlane_1', false);
    }
}
