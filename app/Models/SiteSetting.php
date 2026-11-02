<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['setting_key', 'setting_value', 'description', 'updated_by'])]
class SiteSetting extends Model
{
    protected function casts(): array
    {
        return ['setting_value' => 'array'];
    }
}
