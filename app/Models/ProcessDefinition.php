<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'process_key',
    'name',
    'version',
    'status',
    'bpmn_xml',
    'created_by',
    'published_at',
    'engine_deployment_id',
    'engine_process_definition_id',
    'engine_deployed_at',
    'engine_deployment_error',
])]
class ProcessDefinition extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'engine_deployed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isDeployed(): bool
    {
        return filled($this->engine_deployment_id) && filled($this->engine_deployed_at);
    }
}
