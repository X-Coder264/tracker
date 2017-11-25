<?php

use App\Http\Models\Locale;
use Illuminate\Database\Seeder;

class LocaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $locale = new Locale();
        $locale->locale = 'English';
        $locale->localeShort = 'en';
        $locale->save();

        $locale = new Locale();
        $locale->locale = 'Croatian';
        $locale->localeShort = 'hr';
        $locale->save();
    }
}
