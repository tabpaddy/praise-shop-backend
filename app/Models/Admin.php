<?php

namespace App\Models;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'subAdmin'];

    protected $hidden = ['password', 'remember_token'];


    public function isAdmin()
    {
        // Assuming you have a column `role` in your `admins` table to identify admins
        return $this->subAdmin === false;
    }

    // In App\Models\Admin.php
    public function isAdminOrSubAdmin()
{
    // Debug the subAdmin value and type
    Log::debug('Checking isAdminOrSubAdmin', [
        'subAdmin' => $this->subAdmin,
        'subAdminType' => gettype($this->subAdmin),
    ]);

    // Handle integer values (0 for full admin, 1 for sub-admin)
    return $this->subAdmin == 0 || $this->subAdmin == 1;
}
}
