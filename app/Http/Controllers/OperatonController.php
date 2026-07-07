<?php

namespace App\Http\Controllers;

use App\Models\ProcessDefinition;
use App\Services\Operaton\OperatonClient;
use App\Services\Operaton\OperatonException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OperatonController extends Controller
{
    public function dashboard(OperatonClient $operaton): View
    {
        $status = $operaton->status();
        $engineDefinitions = [];

        if ($status['ok']) {
            try {
                $engineDefinitions = $operaton->processDefinitions();
            } catch (OperatonException $exception) {
                $status['ok'] = false;
                $status['message'] = $exception->getMessage();
            }
        }

        $publishedDefinitions = ProcessDefinition::query()
            ->with('creator')
            ->where('status', ProcessDefinition::STATUS_PUBLISHED)
            ->orderBy('process_key')
            ->orderByDesc('version')
            ->get();

        return view('operaton.dashboard', [
            'status' => $status,
            'engineDefinitions' => $engineDefinitions,
            'publishedDefinitions' => $publishedDefinitions,
        ]);
    }

    public function deploy(ProcessDefinition $processDefinition, OperatonClient $operaton): RedirectResponse
    {
        if (! $processDefinition->isPublished()) {
            return back()->withErrors([
                'deploy' => 'Only published process versions can be deployed to Operaton.',
            ]);
        }

        try {
            $deployment = $operaton->deploy($processDefinition);
        } catch (OperatonException $exception) {
            $processDefinition->forceFill([
                'engine_deployment_error' => $exception->getMessage(),
            ])->save();

            return back()->withErrors([
                'deploy' => $exception->getMessage(),
            ]);
        }

        $processDefinition->forceFill([
            'engine_deployment_id' => $deployment['deployment_id'],
            'engine_process_definition_id' => $deployment['process_definition_id'],
            'engine_deployed_at' => now(),
            'engine_deployment_error' => null,
        ])->save();

        return redirect()
            ->route('process-definitions.show', $processDefinition)
            ->with('status', 'Published definition deployed to Operaton successfully.');
    }
}
