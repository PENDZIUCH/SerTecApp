<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_key',
        'title',
        'html_content',
        'css_content',
        'is_active',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeKey($query, $key)
    {
        return $query->where('template_key', $key);
    }

    public static function getByKey($key)
    {
        return static::where('template_key', $key)
            ->where('is_active', true)
            ->first();
    }

    public function render($data = [])
    {
        $html = $this->html_content;
        
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }

        return $html;
    }
}
