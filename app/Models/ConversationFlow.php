<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationFlow extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'start_node_id',
    ];

    public function nodes()
    {
        return $this->hasMany(FlowNode::class, 'flow_id');
    }

    public function edges()
    {
        return $this->hasMany(FlowEdge::class, 'flow_id');
    }

    public function startNode()
    {
        return $this->belongsTo(FlowNode::class, 'start_node_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public static function findPublished(string $code): ?self
    {
        return static::where('code', $code)->where('status', 'published')->first();
    }
}
