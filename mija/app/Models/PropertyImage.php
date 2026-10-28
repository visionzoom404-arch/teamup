<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PropertyImage extends Model
{
    protected $guarded = [];

    public function url(): string
    {
        return Str::startsWith($this->path, ['http', '/']) ? $this->path : asset('storage/'.$this->path);
    }
}
