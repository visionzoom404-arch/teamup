<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    /** آیکن سه‌بعدی: ابتدا نام ذخیره‌شده، وگرنه نگاشت بر اساس کلید */
    public function i3(): string
    {
        return in_array($this->icon, \App\Support\Icons3D::names(), true) ? $this->icon : \App\Support\Icons3D::amenity($this->key);
    }
}
