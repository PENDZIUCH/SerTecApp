<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'level',
        'message',
        'context',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeError($query)
    {
        return $query->where('level', 'error');
    }

    public function scopeCritical($query)
    {
        return $query->where('level', 'critical');
    }

    public function scopeWarning($query)
    {
        return $query->where('level', 'warning');
    }

    public function scopeInfo($query)
    {
        return $query->where('level', 'info');
    }

    public static function log($level, $message, $context = [])
    {
        return static::create([
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'created_at' => now(),
        ]);
    }

    public static function error($message, $context = [])
    {
        return static::log('error', $message, $context);
    }

    public static function critical($message, $context = [])
    {
        return static::log('critical', $message, $context);
    }

    public static function warning($message, $context = [])
    {
        return static::log('warning', $message, $context);
    }

    public static function info($message, $context = [])
    {
        return static::log('info', $message, $context);
    }
}
