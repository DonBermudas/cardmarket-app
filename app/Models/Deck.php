<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deck extends Model
{
    use HasFactory;
    protected $table = 'cards_collections'; // Vincula este modelo con la tabla donde quiero registrarlo
}
