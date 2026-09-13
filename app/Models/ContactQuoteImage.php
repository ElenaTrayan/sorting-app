<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactQuoteImage extends Model
{
    protected $table = 'contact_quote_images';

    protected $fillable = [
        'contact_quote_id',
        'image',
    ];

    public function quote()
    {
        return $this->belongsTo(ContactQuote::class, 'contact_quote_id');
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
