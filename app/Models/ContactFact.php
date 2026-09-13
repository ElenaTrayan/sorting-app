<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactFact extends Model
{
    protected $table = 'contact_facts';

    protected $fillable = [
        'contact_id',
        'fact',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
