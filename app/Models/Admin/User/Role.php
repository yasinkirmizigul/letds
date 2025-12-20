<?php

namespace App\Models\Admin\User;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $casts = [
        'priority' => 'integer',
    ];
    protected $fillable = ['name', 'slug'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
