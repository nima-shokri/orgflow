<?php

namespace App\Http\Controllers;

use App\Models\ProcessDefinition;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProcessDefinitionController extends Controller
{
    public function index(): View
    {
        $families = ProcessDefinition::query()
            ->with('creator')
            ->orderBy('process_key')
            ->orderByDesc('version')
            ->get()
            ->groupBy('process_key');

        $versions = $families->flatten(1);

        return view('process-definitions.index', [
            'families' => $families,
            'familyCount' => $families->count(),
            'versionCount' => $versions->count(),
            'publishedCount' => $versions->where('status', ProcessDefinition::STATUS_PUBLISHED)->count(),
        ]);
    }

    public function create(): View
    {
        return view('process-definitions.form', [
            'mode' => 'create',
            'processKey' => old('process_key', 'invoice-approval'),
            'name' => old('name', 'Invoice Approval'),
            'bpmnXml' => old('bpmn_xml', $this->sampleBpmnXml('invoice-approval', 'Invoice Approval')),
            'nextVersion' => 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'process_key' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9._-]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'bpmn_xml' => ['required', 'string'],
            'intent' => ['required', Rule::in(['draft', 'publish'])],
        ]);

        $processKey = Str::lower($validated['process_key']);

        if (ProcessDefinition::query()->where('process_key', $processKey)->exists()) {
            throw ValidationException::withMessages([
                'process_key' => 'This process key already exists. Create the next version from the existing definition instead.',
            ]);
        }

        $this->assertValidBpmnXml($validated['bpmn_xml']);

        $definition = $this->createDefinition(
            processKey: $processKey,
            name: $validated['name'],
            bpmnXml: $validated['bpmn_xml'],
            version: 1,
            publish: $validated['intent'] === 'publish',
            userId: $request->user()->id,
        );

        return redirect()
            ->route('process-definitions.show', $definition)
            ->with('status', 'Process family created successfully.');
    }

    public function show(ProcessDefinition $processDefinition): View
    {
        $processDefinition->load('creator');

        $versions = ProcessDefinition::query()
            ->with('creator')
            ->where('process_key', $processDefinition->process_key)
            ->orderByDesc('version')
            ->get();

        return view('process-definitions.show', [
            'definition' => $processDefinition,
            'versions' => $versions,
            'xmlDetails' => $this->extractBpmnDetails($processDefinition->bpmn_xml),
        ]);
    }

    public function createVersion(ProcessDefinition $processDefinition): View
    {
        $nextVersion = ProcessDefinition::query()
            ->where('process_key', $processDefinition->process_key)
            ->max('version') + 1;

        return view('process-definitions.form', [
            'mode' => 'version',
            'baseDefinition' => $processDefinition,
            'processKey' => $processDefinition->process_key,
            'name' => old('name', $processDefinition->name),
            'bpmnXml' => old('bpmn_xml', $processDefinition->bpmn_xml),
            'nextVersion' => $nextVersion,
        ]);
    }

    public function storeVersion(Request $request, ProcessDefinition $processDefinition): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bpmn_xml' => ['required', 'string'],
            'intent' => ['required', Rule::in(['draft', 'publish'])],
        ]);

        $this->assertValidBpmnXml($validated['bpmn_xml']);

        $nextVersion = ProcessDefinition::query()
            ->where('process_key', $processDefinition->process_key)
            ->max('version') + 1;

        $definition = $this->createDefinition(
            processKey: $processDefinition->process_key,
            name: $validated['name'],
            bpmnXml: $validated['bpmn_xml'],
            version: $nextVersion,
            publish: $validated['intent'] === 'publish',
            userId: $request->user()->id,
        );

        return redirect()
            ->route('process-definitions.show', $definition)
            ->with('status', 'New process version created successfully.');
    }

    public function publish(ProcessDefinition $processDefinition): RedirectResponse
    {
        DB::transaction(function () use ($processDefinition): void {
            ProcessDefinition::query()
                ->where('process_key', $processDefinition->process_key)
                ->where('status', ProcessDefinition::STATUS_PUBLISHED)
                ->whereKeyNot($processDefinition->id)
                ->update([
                    'status' => ProcessDefinition::STATUS_ARCHIVED,
                ]);

            $processDefinition->forceFill([
                'status' => ProcessDefinition::STATUS_PUBLISHED,
                'published_at' => now(),
            ])->save();
        });

        return redirect()
            ->route('process-definitions.show', $processDefinition)
            ->with('status', 'Selected process version is now published.');
    }

    private function createDefinition(
        string $processKey,
        string $name,
        string $bpmnXml,
        int $version,
        bool $publish,
        int $userId,
    ): ProcessDefinition {
        return DB::transaction(function () use ($processKey, $name, $bpmnXml, $version, $publish, $userId): ProcessDefinition {
            $status = $publish
                ? ProcessDefinition::STATUS_PUBLISHED
                : ProcessDefinition::STATUS_DRAFT;

            $definition = ProcessDefinition::create([
                'process_key' => $processKey,
                'name' => $name,
                'version' => $version,
                'status' => $status,
                'bpmn_xml' => $bpmnXml,
                'created_by' => $userId,
                'published_at' => $publish ? now() : null,
            ]);

            if ($publish) {
                ProcessDefinition::query()
                    ->where('process_key', $processKey)
                    ->where('status', ProcessDefinition::STATUS_PUBLISHED)
                    ->whereKeyNot($definition->id)
                    ->update([
                        'status' => ProcessDefinition::STATUS_ARCHIVED,
                    ]);
            }

            return $definition;
        });
    }

    private function assertValidBpmnXml(string $bpmnXml): void
    {
        $previous = libxml_use_internal_errors(true);

        $document = new DOMDocument();
        $loaded = $document->loadXML($bpmnXml);

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw ValidationException::withMessages([
                'bpmn_xml' => 'The BPMN XML could not be parsed. Please provide valid XML.',
            ]);
        }

        $xpath = new DOMXPath($document);

        $hasDefinitions = (int) $xpath->evaluate('count(/*[local-name()="definitions"])') > 0;
        $hasProcess = (int) $xpath->evaluate('count(//*[local-name()="process"])') > 0;

        if (! $hasDefinitions || ! $hasProcess) {
            throw ValidationException::withMessages([
                'bpmn_xml' => 'The BPMN XML must contain a <definitions> root node and at least one <process> node.',
            ]);
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'bpmn_xml' => 'The BPMN XML contains parser errors. Please re-check the document structure.',
            ]);
        }
    }

    /**
     * @return array{process_ids: array<int, string>, root: string}
     */
    private function extractBpmnDetails(string $bpmnXml): array
    {
        $document = new DOMDocument();
        $document->loadXML($bpmnXml);

        $xpath = new DOMXPath($document);

        $processIds = [];

        foreach ($xpath->query('//*[local-name()="process"]') ?: [] as $processNode) {
            $identifier = $processNode->attributes?->getNamedItem('id')?->nodeValue;

            if ($identifier) {
                $processIds[] = $identifier;
            }
        }

        return [
            'root' => $document->documentElement?->nodeName ?? 'definitions',
            'process_ids' => $processIds,
        ];
    }

    private function sampleBpmnXml(string $processKey, string $name): string
    {
        $safeProcessKey = $this->escapeXmlAttribute($processKey);
        $safeName = $this->escapeXmlAttribute($name);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
                  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
                  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
                  xmlns:di="http://www.omg.org/spec/DD/20100524/DI"
                  id="Definitions_{$safeProcessKey}"
                  targetNamespace="http://bpms.local/bpmn">
  <bpmn:process id="{$safeProcessKey}" name="{$safeName}" isExecutable="true">
    <bpmn:startEvent id="StartEvent_1" name="Start">
      <bpmn:outgoing>Flow_1</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:userTask id="Activity_Review" name="Review request">
      <bpmn:incoming>Flow_1</bpmn:incoming>
      <bpmn:outgoing>Flow_2</bpmn:outgoing>
    </bpmn:userTask>
    <bpmn:endEvent id="EndEvent_1" name="Done">
      <bpmn:incoming>Flow_2</bpmn:incoming>
    </bpmn:endEvent>
    <bpmn:sequenceFlow id="Flow_1" sourceRef="StartEvent_1" targetRef="Activity_Review" />
    <bpmn:sequenceFlow id="Flow_2" sourceRef="Activity_Review" targetRef="EndEvent_1" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="{$safeProcessKey}">
      <bpmndi:BPMNShape id="StartEvent_1_di" bpmnElement="StartEvent_1">
        <dc:Bounds x="160" y="102" width="36" height="36" />
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="Activity_Review_di" bpmnElement="Activity_Review">
        <dc:Bounds x="270" y="80" width="140" height="80" />
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="EndEvent_1_di" bpmnElement="EndEvent_1">
        <dc:Bounds x="490" y="102" width="36" height="36" />
      </bpmndi:BPMNShape>
      <bpmndi:BPMNEdge id="Flow_1_di" bpmnElement="Flow_1">
        <di:waypoint x="196" y="120" />
        <di:waypoint x="270" y="120" />
      </bpmndi:BPMNEdge>
      <bpmndi:BPMNEdge id="Flow_2_di" bpmnElement="Flow_2">
        <di:waypoint x="410" y="120" />
        <di:waypoint x="490" y="120" />
      </bpmndi:BPMNEdge>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>
XML;
    }

    private function escapeXmlAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
