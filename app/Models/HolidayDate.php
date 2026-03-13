<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidayDate extends Model
{
    protected $fillable = [
        'date',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public static function isHoliday(\DateTimeInterface $date): bool
    {
        return static::where('date', $date->format('Y-m-d'))->exists();
    }
}
