<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowEdge extends Model
{
    protected $fillable = [
        'flow_id',
        'from_node_id',
        'to_node_id',
        'condition_type',
        'condition_value',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'condition_value' => 'array',
        ];
    }

    public function flow()
    {
        return $this->belongsTo(ConversationFlow::class, 'flow_id');
    }

    public function fromNode()
    {
        return $this->belongsTo(FlowNode::class, 'from_node_id');
    }

    public function toNode()
    {
        return $this->belongsTo(FlowNode::class, 'to_node_id');
    }
}
