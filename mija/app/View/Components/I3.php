<?php

namespace App\View\Components;

use App\Support\Icons3D;
use Illuminate\View\Component;

/** آیکن سه‌بعدی هم‌رنگ تم (طلایی-نارنجی/کرم/مشکی) */
class I3 extends Component
{
    public function __construct(public string $name, public ?string $size = null) {}

    public function render()
    {
        return function (array $data) {
            $svg = Icons3D::svg($this->name, $data['attributes']->get('class'));
            if ($this->size) {
                $svg = str_replace('<svg class="i3', '<svg style="width:'.$this->size.'px;height:'.$this->size.'px" class="i3', $svg);
            }

            return $svg;
        };
    }
}
