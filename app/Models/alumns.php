<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class alumns extends Model
{
    use HasFactory;
    protected $table = 'alumns';
    protected $fillable = [
        'Name',
        'LastName',
        'SecondLastName',
        'Birthday',
        'Gender',
        'StudyLevel',
        'Email',
        'Phone'
    ];
}
