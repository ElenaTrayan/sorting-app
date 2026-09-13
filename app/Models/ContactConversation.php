<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactConversation extends Model
{
    protected $table = 'contact_conversations';

    protected $fillable = [
        'contact_id',
        'image',
        'caption',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function getImageDataAttribute(): ?array
    {
        if (empty($this->attributes['image'])) {
            return null;
        }

        $decoded = json_decode($this->attributes['image'], true);

        return is_array($decoded) ? $decoded : null;
    }

    public function getImageUrlAttribute(): ?string
    {
        $data = $this->image_data;

        return !empty($data['path']) ? '/storage' . $data['path'] : null;
    }
}
