<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\Component;

/**
 * سازگاری قدیمی: <x-i3 name="..."/> حالا آیکن خطی و ظریف نمایش می‌دهد (طراحی لوکس و مینیمال).
 */
class I3 extends Component
{
    private const MAP = [
        'villa' => 'home', 'cabin' => 'home', 'tiny' => 'home', 'garden_villa' => 'leaf', 'eco' => 'mountain', 'suite' => 'bed', 'farm' => 'leaf', 'apartment' => 'building',
        'pool' => 'pool', 'jacuzzi' => 'jacuzzi', 'sauna' => 'sauna', 'bbq' => 'bbq', 'fireplace' => 'fireplace', 'garden' => 'leaf', 'view' => 'mountain', 'playground' => 'slide',
        'wifi' => 'wifi', 'parking' => 'parking', 'ac' => 'snow', 'heating' => 'flame', 'kitchen' => 'kitchen', 'fridge' => 'fridge', 'tv' => 'tv', 'washer' => 'washer', 'bedding' => 'bed',
        'pet' => 'paw', 'family' => 'users', 'cup' => 'cup', 'host' => 'user', 'ev' => 'plug',
        'shield' => 'shield', 'p_shield' => 'shield', 'bolt' => 'bolt', 'p_bolt' => 'bolt', 'tag' => 'tag', 'p_tag' => 'tag', 'star' => 'star', 'p_star' => 'star',
        'search' => 'search', 'p_search' => 'search', 'key' => 'key', 'p_key' => 'key', 'wallet' => 'wallet', 'p_wallet' => 'wallet', 'calendar' => 'calendar', 'p_calendar' => 'calendar',
        'coins' => 'coins', 'p_coin' => 'coins', 'chat' => 'chat', 'p_chat' => 'chat', 'bell' => 'bell', 'p_bell' => 'bell', 'gift' => 'sparkle', 'p_gift' => 'sparkle',
        'heart' => 'heart', 'p_heart' => 'heart', 'trophy' => 'award', 'p_trophy' => 'award', 'crown' => 'award', 'p_crown' => 'award', 'pin' => 'pin', 'p_pin' => 'pin',
        'sun' => 'sun', 'trend' => 'trend', 'home' => 'home', 'p_house' => 'home', 'user' => 'user', 'lock' => 'lock', 'p_lock' => 'lock', 'card' => 'wallet', 'phone' => 'phone',
        'camera' => 'image', 'p_camera' => 'image', 'wave' => 'sparkle', 'sparkle' => 'sparkle', 'party' => 'sparkle', 'clock' => 'clock', 'p_hourglass' => 'clock', 'p_alarm' => 'clock',
        'check' => 'check', 'map' => 'pin', 'receipt' => 'receipt', 'khatam' => 'sparkle', 'mountain' => 'mountain', 'tent' => 'mountain', 'campfire' => 'flame', 'lantern' => 'sun',
        'samovar' => 'cup', 'carpet' => 'grid', 'palm' => 'leaf', 'sea' => 'pool', 'cloud' => 'sun', 'snowflake' => 'snow', 'leaf' => 'leaf', 'binoculars' => 'eye', 'backpack' => 'suitcase',
        'suitcase' => 'suitcase', 'p_suitcase' => 'suitcase', 'compass' => 'compass', 'p_compass' => 'compass', 'car' => 'send', 'door' => 'door', 'sofa' => 'sofa', 'lamp' => 'sun',
        'moon' => 'sun', 'dome' => 'building', 'tulip' => 'leaf', 'parasol' => 'sun', 'balloon' => 'sparkle', 'p_balloon' => 'sparkle', 'bike' => 'compass',
    ];

    public function __construct(public string $name, public ?string $size = null) {}

    public function render()
    {
        return function (array $data) {
            $icon = self::MAP[$this->name] ?? 'sparkle';
            $class = trim(($data['attributes']->get('class') ?? '').' i3l');

            return Blade::render('<x-icon :name="$n" :class="$c"/>', ['n' => $icon, 'c' => $class]);
        };
    }
}
