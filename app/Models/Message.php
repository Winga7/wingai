<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
  use HasFactory;

  protected $fillable = [
    'conversation_id',
    'content',
    'role',
    'model',
    'image_url',    // Ajout du nouveau champ
    'images',       // Conservation pour compatibilité
    'has_images'    // Conservation pour compatibilité
  ];

  protected $casts = [
    'images' => 'array',
    'has_images' => 'boolean'
  ];

  // Ajout d'accesseurs pour la compatibilité
  public function getImageAttribute()
  {
    return $this->image_url ?? ($this->images[0] ?? null);
  }

  public function conversation(): BelongsTo
  {
    return $this->belongsTo(Conversation::class);
  }
}
