<?php

namespace App\Models;

use Database\Factories\SearchCredentialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchCredential extends Model
{
    /** @use HasFactory<SearchCredentialFactory> */
    use HasFactory;

    protected $fillable = ['provider', 'name', 'value', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }
}
