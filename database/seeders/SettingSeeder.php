<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSettings = [
            'library_name' => 'সূর্যোদয় যুব পাঠাগার',
            'tagline'      => 'জ্ঞানের আলো ছড়িয়ে যাক প্রতিটি ঘরে',
            'mission'      => 'আমাদের এলাকার তরুণ ও শিক্ষার্থীদের অবসর সময়ে বই পড়ার অভ্যাস গড়ে তোলা।',
            'vision'       => 'একটি মাদক ও অপরাধমুক্ত আদর্শ সমাজ বিনির্মাণ।',
            'phone'        => '০১৭১১-xxxxxx',
            'address'      => 'গ্রাম/এলাকা: রূপনগর, ওয়ার্ড নং ৩',
        ];

        foreach ($defaultSettings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
