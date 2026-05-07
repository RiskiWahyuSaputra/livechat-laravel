<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Division extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'supervisor_id'];

    public function supervisor()
    {
        return $this->belongsTo(Admin::class, 'supervisor_id');
    }

    public function agents()
    {
        return $this->hasMany(Admin::class, 'division', 'slug');
    }

    protected static function booted(): void
    {
        static::creating(function (Division $division) {
            if (empty($division->slug)) {
                $division->slug = Str::slug($division->name);
            }
        });
    }
}
