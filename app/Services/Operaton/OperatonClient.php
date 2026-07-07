<?php

namespace App\Services\Operaton;

use App\Models\ProcessDefinition;
use DOMDocument;
use DOMXPath;
use stdClass;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

class OperatonClient
{
    private const CAMUNDA_BPMN_NAMESPACE = 'http://camunda.org/schema/1.0/bpmn';

    /**
     * @return array{ok: bool, version: string|null, base_url: string, web_url: string, message: string}
     */
    public function status(): array
    {
        try {
            ['response' => $response, 'base_url' => $baseUrl] = $this->send(
                method: 'GET',
                path: '/version',
            );

            return [
                'ok' => true,
                'version' => $response->json('version'),
                'base_url' => $baseUrl,
                'web_url' => (string) config('operaton.web_url'),
                'message' => 'Operaton is reachable from Laravel.',
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'version' => null,
                'base_url' => $this->configuredBaseUrl(),
                'web_url' => (string) config('operaton.web_url'),
                'message' => $this->exceptionMessage($exception),
            ];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function processDefinitions(): array
    {
        try {
            ['response' => $response] = $this->send(
                method: 'GET',
                path: '/process-definition',
                data: [
                    'latestVersion' => 'true',
                    'sortBy' => 'key',
                    'sortOrder' => 'asc',
                ],
            );

            return $response->json();
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    /**
     * @return array{
     *     deployment_id: string|null,
     *     deployment_name: string|null,
     *     process_definition_id: string|null,
     *     process_definition_key: string|null
     * }
     */
    public function deploy(ProcessDefinition $definition): array
    {
        $filename = sprintf('%s-v%d.bpmn', $definition->process_key, $definition->version);
        $deployableXml = $this->prepareDeployableBpmnXml($definition->bpmn_xml);

        try {
            ['response' => $response] = $this->send(
                method: 'POST',
                path: '/deployment/create',
                data: [
                    'deployment-name' => sprintf('%s v%d', $definition->process_key, $definition->version),
                    'deployment-source' => 'bpms-laravel',
                    'enable-duplicate-filtering' => 'true',
                    'deploy-changed-only' => 'true',
                ],
                configure: fn (PendingRequest $request) => $request->attach(
                    'data',
                    $deployableXml,
                    $filename,
                    ['Content-Type' => 'application/xml'],
                ),
            );

            $payload = $response->json();
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }

        $processDefinition = Collection::make($payload['deployedProcessDefinitions'] ?? [])->first();

        return [
            'deployment_id' => $payload['id'] ?? null,
            'deployment_name' => $payload['name'] ?? null,
            'process_definition_id' => data_get($processDefinition, 'id'),
            'process_definition_key' => data_get($processDefinition, 'key'),
        ];
    }

    /**
     * @return array<string, mixed>
     * @param  array<string, bool|int|float|string|null>  $variables
     */
    public function startProcessInstance(
        ProcessDefinition $definition,
        ?string $businessKey = null,
        array $variables = [],
    ): array
    {
        $definitionId = $definition->engine_process_definition_id;

        if (! is_string($definitionId) || $definitionId === '') {
            throw new OperatonException('This process definition is not deployed in Operaton yet.');
        }

        $payload = array_filter([
            'businessKey' => filled($businessKey) ? $businessKey : null,
            'variables' => $variables !== [] ? $this->normalizeVariablePayload($variables) : null,
        ], static fn ($value) => $value !== null);

        try {
            ['response' => $response] = $this->send(
                method: 'POST',
                path: sprintf('/process-definition/%s/start', rawurlencode($definitionId)),
                data: $payload === [] ? new stdClass() : $payload,
            );

            return $response->json();
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentProcessInstances(?string $processDefinitionId = null, int $limit = 25): array
    {
        $query = [
            'sortBy' => 'startTime',
            'sortOrder' => 'desc',
            'maxResults' => $limit,
        ];

        if (filled($processDefinitionId)) {
            $query['processDefinitionId'] = $processDefinitionId;
        }

        try {
            ['response' => $response] = $this->send(
                method: 'GET',
                path: '/history/process-instance',
                data: $query,
            );

            return $response->json();
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function processInstance(string $instanceId): array
    {
        try {
            $history = $this->fetchJsonOrNull(
                path: sprintf('/history/process-instance/%s', rawurlencode($instanceId)),
            );

            $runtime = $this->fetchJsonOrNull(
                path: sprintf('/process-instance/%s', rawurlencode($instanceId)),
            );
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }

        if ($history === null && $runtime === null) {
            throw new OperatonException('The requested process instance was not found in Operaton.');
        }

        return array_filter([
            'id' => data_get($history, 'id', data_get($runtime, 'id', $instanceId)),
            'processDefinitionId' => data_get($history, 'processDefinitionId', data_get($runtime, 'definitionId')),
            'processDefinitionKey' => data_get($history, 'processDefinitionKey'),
            'processDefinitionName' => data_get($history, 'processDefinitionName'),
            'processDefinitionVersion' => data_get($history, 'processDefinitionVersion'),
            'businessKey' => data_get($history, 'businessKey', data_get($runtime, 'businessKey')),
            'startTime' => data_get($history, 'startTime'),
            'endTime' => data_get($history, 'endTime'),
            'durationInMillis' => data_get($history, 'durationInMillis'),
            'state' => data_get($history, 'state', $this->deriveRuntimeState($runtime)),
            'suspended' => data_get($runtime, 'suspended'),
            'ended' => data_get($runtime, 'ended', $this->deriveEndedState($history)),
            'startActivityId' => data_get($history, 'startActivityId'),
            'rootProcessInstanceId' => data_get($history, 'rootProcessInstanceId'),
            'deleteReason' => data_get($history, 'deleteReason'),
        ], static fn ($value) => $value !== null);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeTasks(string $instanceId): array
    {
        try {
            ['response' => $response] = $this->send(
                method: 'GET',
                path: '/task',
                data: [
                    'processInstanceId' => $instanceId,
                ],
            );

            return $response->json();
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function processVariables(string $instanceId): array
    {
        try {
            $variables = $this->fetchProcessScopedList(
                path: '/history/variable-instance',
                queryCandidates: [
                    [
                        'processInstanceId' => $instanceId,
                        'deserializeValues' => 'false',
                    ],
                    [
                        'processInstanceIdIn' => $instanceId,
                        'deserializeValues' => 'false',
                    ],
                ],
                processInstanceId: $instanceId,
            );

            return Collection::make($variables)
                ->sortBy(fn (array $variable): string => strtolower((string) ($variable['name'] ?? '')))
                ->values()
                ->all();
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activityHistory(string $instanceId): array
    {
        try {
            $activities = $this->fetchProcessScopedList(
                path: '/history/activity-instance',
                queryCandidates: [
                    [
                        'processInstanceId' => $instanceId,
                    ],
                    [
                        'processInstanceIdIn' => $instanceId,
                    ],
                ],
                processInstanceId: $instanceId,
            );

            return Collection::make($activities)
                ->sortBy(fn (array $activity): string => sprintf(
                    '%s|%s',
                    (string) ($activity['startTime'] ?? ''),
                    (string) ($activity['id'] ?? ''),
                ))
                ->values()
                ->all();
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tasks(
        ?string $processInstanceId = null,
        ?string $processDefinitionId = null,
        int $limit = 50,
    ): array {
        $query = [
            'sortBy' => 'created',
            'sortOrder' => 'desc',
            'maxResults' => $limit,
        ];

        if (filled($processInstanceId)) {
            $query['processInstanceId'] = $processInstanceId;
        }

        if (filled($processDefinitionId)) {
            $query['processDefinitionId'] = $processDefinitionId;
        }

        try {
            ['response' => $response] = $this->send(
                method: 'GET',
                path: '/task',
                data: $query,
            );

            return $response->json();
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function task(string $taskId): array
    {
        try {
            ['response' => $response] = $this->send(
                method: 'GET',
                path: sprintf('/task/%s', rawurlencode($taskId)),
            );

            return $response->json();
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function taskFormVariables(string $taskId): array
    {
        try {
            ['response' => $response] = $this->send(
                method: 'GET',
                path: sprintf('/task/%s/form-variables', rawurlencode($taskId)),
                data: [
                    'deserializeValues' => 'false',
                ],
            );

            return (array) ($response->json() ?? []);
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    /**
     * @param  array<string, bool|int|float|string|null>  $variables
     */
    public function submitTaskForm(string $taskId, array $variables = []): void
    {
        $payload = [
            'variables' => $this->normalizeVariablePayload($variables),
        ];

        try {
            $this->send(
                method: 'POST',
                path: sprintf('/task/%s/submit-form', rawurlencode($taskId)),
                data: $payload,
            );
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    /**
     * @param  array<string, bool|int|float|string|null>  $variables
     */
    public function completeTask(string $taskId, array $variables = []): void
    {
        $payload = [
            'variables' => $this->normalizeVariablePayload($variables),
        ];

        try {
            $this->send(
                method: 'POST',
                path: sprintf('/task/%s/complete', rawurlencode($taskId)),
                data: $payload,
            );
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    public function claimTask(string $taskId, string $userId): void
    {
        try {
            $this->send(
                method: 'POST',
                path: sprintf('/task/%s/claim', rawurlencode($taskId)),
                data: [
                    'userId' => $userId,
                ],
            );
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    public function unclaimTask(string $taskId): void
    {
        try {
            $this->send(
                method: 'POST',
                path: sprintf('/task/%s/unclaim', rawurlencode($taskId)),
                data: [],
            );
        } catch (Throwable $exception) {
            throw new OperatonException($this->exceptionMessage($exception), previous: $exception);
        }
    }

    /**
     * @param  array<string, mixed>|stdClass  $data
     * @return array{response: Response, base_url: string}
     */
    private function send(
        string $method,
        string $path,
        array|stdClass $data = [],
        ?callable $configure = null,
    ): array {
        $baseUrls = $this->candidateBaseUrls();
        $lastException = null;

        foreach ($baseUrls as $index => $baseUrl) {
            $request = $this->request($baseUrl);

            if ($configure) {
                $request = $configure($request);
            }

            try {
                $response = match ($method) {
                    'GET' => $request->get($path, is_array($data) ? $data : []),
                    'POST' => $request->post($path, $data === [] ? new stdClass() : $data),
                    default => throw new OperatonException("Unsupported Operaton request method [$method]."),
                };

                return [
                    'response' => $response->throw(),
                    'base_url' => $baseUrl,
                ];
            } catch (Throwable $exception) {
                $lastException = $exception;

                if (! $this->shouldTryNextBaseUrl($exception, $index, count($baseUrls))) {
                    throw $exception;
                }
            }
        }

        throw $lastException ?? new OperatonException('Could not reach Operaton from Laravel.');
    }

    private function request(string $baseUrl): PendingRequest
    {
        $request = Http::acceptJson()
            ->timeout((int) config('operaton.timeout'))
            ->baseUrl($baseUrl);

        $username = (string) config('operaton.username');
        $password = (string) config('operaton.password');

        if ($username !== '' || $password !== '') {
            $request = $request->withBasicAuth($username, $password);
        }

        return $request;
    }

    /**
     * @return array<int, string>
     */
    private function candidateBaseUrls(): array
    {
        $configured = $this->configuredBaseUrl();
        $candidates = [$configured];

        if (str_contains($configured, '/operaton/engine-rest')) {
            $candidates[] = str_replace('/operaton/engine-rest', '/engine-rest', $configured);
        } elseif (str_contains($configured, '/engine-rest')) {
            $candidates[] = str_replace('/engine-rest', '/operaton/engine-rest', $configured);
        } else {
            $candidates[] = rtrim($configured, '/') . '/engine-rest';
            $candidates[] = rtrim($configured, '/') . '/operaton/engine-rest';
        }

        return array_values(array_unique(array_map(
            static fn (string $url) => rtrim($url, '/'),
            $candidates,
        )));
    }

    private function configuredBaseUrl(): string
    {
        return rtrim((string) config('operaton.base_url'), '/');
    }

    private function shouldTryNextBaseUrl(Throwable $exception, int $index, int $count): bool
    {
        if ($index >= $count - 1) {
            return false;
        }

        return $exception instanceof RequestException
            && $exception->response !== null
            && $exception->response->status() === 404;
    }

    private function exceptionMessage(Throwable $exception): string
    {
        if ($exception instanceof RequestException && $exception->response !== null) {
            $message = $exception->response->json('message');

            if (is_string($message) && $message !== '') {
                return $message;
            }

            $body = trim(preg_replace('/\s+/', ' ', strip_tags($exception->response->body())) ?? '');

            if ($body !== '') {
                return $body;
            }

            if ($exception->response->status() === 404) {
                return 'The configured Operaton REST endpoint was not found. Verify OPERATON_BASE_URL and prefer /engine-rest.';
            }

            return sprintf(
                'Operaton returned HTTP %d while processing the request.',
                $exception->response->status(),
            );
        }

        return $exception->getMessage() !== ''
            ? $exception->getMessage()
            : 'Could not reach Operaton from Laravel.';
    }

    /**
     * @param  array<string, mixed>|null  $runtime
     */
    private function deriveRuntimeState(?array $runtime): ?string
    {
        if ($runtime === null) {
            return null;
        }

        if (data_get($runtime, 'suspended') === true) {
            return 'SUSPENDED';
        }

        if (data_get($runtime, 'ended') === true) {
            return 'COMPLETED';
        }

        return 'ACTIVE';
    }

    /**
     * @param  array<string, mixed>|null  $history
     */
    private function deriveEndedState(?array $history): ?bool
    {
        if ($history === null) {
            return null;
        }

        if (filled(data_get($history, 'endTime'))) {
            return true;
        }

        $state = strtoupper((string) data_get($history, 'state'));

        if ($state === '') {
            return null;
        }

        return $state !== 'ACTIVE';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function fetchJsonOrNull(string $path, array $data = []): ?array
    {
        try {
            ['response' => $response] = $this->send(
                method: 'GET',
                path: $path,
                data: $data,
            );

            return $response->json();
        } catch (RequestException $exception) {
            if ($exception->response !== null && $exception->response->status() === 404) {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $queryCandidates
     * @return array<int, array<string, mixed>>
     */
    private function fetchProcessScopedList(string $path, array $queryCandidates, string $processInstanceId): array
    {
        $lastException = null;

        foreach ($queryCandidates as $index => $query) {
            try {
                ['response' => $response] = $this->send(
                    method: 'GET',
                    path: $path,
                    data: $query,
                );

                $items = $this->normalizeJsonList($response->json());
                $filtered = array_values(array_filter(
                    $items,
                    fn (array $item): bool => ($item['processInstanceId'] ?? null) === $processInstanceId,
                ));

                if ($filtered !== [] || $index === count($queryCandidates) - 1) {
                    return $filtered;
                }
            } catch (Throwable $exception) {
                $lastException = $exception;

                if ($index === count($queryCandidates) - 1) {
                    throw $exception;
                }
            }
        }

        throw $lastException ?? new OperatonException('Could not load the requested Operaton history list.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeJsonList(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        return array_values(array_filter(
            $payload,
            static fn (mixed $item): bool => is_array($item),
        ));
    }

    /**
     * @param  array<string, bool|int|float|string|null>  $variables
     * @return array<string, array<string, bool|int|float|string|null>>
     */
    private function normalizeVariablePayload(array $variables): array|stdClass
    {
        if ($variables === []) {
            return new stdClass();
        }

        $normalized = [];

        foreach ($variables as $name => $value) {
            $normalized[(string) $name] = [
                'value' => $value,
            ];

            if (is_bool($value)) {
                $normalized[(string) $name]['type'] = 'Boolean';

                continue;
            }

            if (is_int($value)) {
                $normalized[(string) $name]['type'] = 'Integer';

                continue;
            }

            if (is_float($value)) {
                $normalized[(string) $name]['type'] = 'Double';

                continue;
            }

            if (is_string($value)) {
                $normalized[(string) $name]['type'] = 'String';
            }
        }

        return $normalized;
    }

    private function prepareDeployableBpmnXml(string $bpmnXml): string
    {
        $defaultHistoryTtl = (int) config('operaton.default_history_ttl', 180);

        if ($defaultHistoryTtl <= 0) {
            return $bpmnXml;
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $loaded = $document->loadXML($bpmnXml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || ! $document->documentElement) {
            return $bpmnXml;
        }

        $definitions = $document->documentElement;

        if (! $definitions->hasAttributeNS('http://www.w3.org/2000/xmlns/', 'camunda')) {
            $definitions->setAttributeNS(
                'http://www.w3.org/2000/xmlns/',
                'xmlns:camunda',
                self::CAMUNDA_BPMN_NAMESPACE,
            );
        }

        $xpath = new DOMXPath($document);

        foreach ($xpath->query('//*[local-name()="process"]') ?: [] as $processNode) {
            if (! $processNode instanceof \DOMElement) {
                continue;
            }

            if (
                $processNode->hasAttributeNS(self::CAMUNDA_BPMN_NAMESPACE, 'historyTimeToLive')
                || $processNode->hasAttribute('camunda:historyTimeToLive')
            ) {
                continue;
            }

            $processNode->setAttributeNS(
                self::CAMUNDA_BPMN_NAMESPACE,
                'camunda:historyTimeToLive',
                (string) $defaultHistoryTtl,
            );
        }

        return $document->saveXML() ?: $bpmnXml;
    }
}
