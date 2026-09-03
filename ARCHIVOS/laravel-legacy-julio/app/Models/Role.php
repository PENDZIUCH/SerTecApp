<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name'];

    public function scopeAdmin($query)
    {
        return $query->where('name', 'admin');
    }

    public function scopeTechnician($query)
    {
        return $query->where('name', 'technician');
    }
}
