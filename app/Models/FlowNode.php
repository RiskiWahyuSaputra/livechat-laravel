<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowNode extends Model
{
    protected $fillable = [
        'flow_id',
        'code',
        'type',
        'content',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'content'  => 'array',
            'position' => 'array',
        ];
    }

    public function flow()
    {
        return $this->belongsTo(ConversationFlow::class, 'flow_id');
    }

    public function outgoingEdges()
    {
        return $this->hasMany(FlowEdge::class, 'from_node_id')->orderBy('priority');
    }

    public function incomingEdges()
    {
        return $this->hasMany(FlowEdge::class, 'to_node_id');
    }
}
