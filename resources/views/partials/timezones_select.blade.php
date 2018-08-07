<div class="form-group">
    <label for="timezone" class="col control-label">{{ trans('messages.timezones_select.timezone') }}</label>
    <div class="col">
        <select class="form-control{{ $errors->has('timezone') ? ' is-invalid' : '' }}" name="timezone" id="timezone" required>
            <option value='Pacific/Midway' @if (auth()->check() && 'Pacific/Midway' === auth()->user()->timezone) selected @endif>(UTC-11:00) Midway Island</option>
            <option value='Pacific/Samoa' @if (auth()->check() && 'Pacific/Samoa' === auth()->user()->timezone) selected @endif>(UTC-11:00) Samoa</option>
            <option value='Pacific/Honolulu' @if (auth()->check() && 'Pacific/Honolulu' === auth()->user()->timezone) selected @endif>(UTC-10:00) Hawaii</option>
            <option value='US/Alaska' @if (auth()->check() && 'US/Alaska' === auth()->user()->timezone) selected @endif>(UTC-09:00) Alaska</option>
            <option value='America/Los_Angeles' @if (auth()->check() && 'America/Los_Angeles' === auth()->user()->timezone) selected @endif>(UTC-08:00) Pacific Time (US &amp; Canada)</option>
            <option value='America/Tijuana' @if (auth()->check() && 'America/Tijuana' === auth()->user()->timezone) selected @endif>(UTC-08:00) Tijuana</option>
            <option value='US/Arizona' @if (auth()->check() && 'US/Arizona' === auth()->user()->timezone) selected @endif>(UTC-07:00) Arizona</option>
            <option value='America/Chihuahua' @if (auth()->check() && 'America/Chihuahua' === auth()->user()->timezone) selected @endif>(UTC-07:00) Chihuahua, La Paz</option>
            <option value='America/Mazatlan' @if (auth()->check() && 'America/Mazatlan' === auth()->user()->timezone) selected @endif>(UTC-07:00) Mazatlan</option>
            <option value='US/Mountain' @if (auth()->check() && 'US/Mountain' === auth()->user()->timezone) selected @endif>(UTC-07:00) Mountain Time (US &amp; Canada)</option>
            <option value='America/Managua' @if (auth()->check() && 'America/Managua' === auth()->user()->timezone) selected @endif>(UTC-06:00) Central America</option>
            <option value='US/Central' @if (auth()->check() && 'US/Central' === auth()->user()->timezone) selected @endif>(UTC-06:00) Central Time (US &amp; Canada)</option>
            <option value='America/Mexico_City' @if (auth()->check() && 'America/Mexico_City' === auth()->user()->timezone) selected @endif>(UTC-06:00) Mexico City, Guadalajara</option>
            <option value='America/Monterrey' @if (auth()->check() && 'America/Monterrey' === auth()->user()->timezone) selected @endif>(UTC-06:00) Monterrey</option>
            <option value='Canada/Saskatchewan' @if (auth()->check() && 'Canada/Saskatchewan' === auth()->user()->timezone) selected @endif>(UTC-06:00) Saskatchewan</option>
            <option value='America/Bogota' @if (auth()->check() && 'America/Bogota' === auth()->user()->timezone) selected @endif>(UTC-05:00) Bogota, Quito</option>
            <option value='US/Eastern' @if (auth()->check() && 'US/Eastern' === auth()->user()->timezone) selected @endif>(UTC-05:00) Eastern Time (US &amp; Canada)</option>
            <option value='US/East-Indiana' @if (auth()->check() && 'US/East-Indiana' === auth()->user()->timezone) selected @endif>(UTC-05:00) Indiana (East)</option>
            <option value='America/Lima' @if (auth()->check() && 'America/Lima' === auth()->user()->timezone) selected @endif>(UTC-05:00) Lima</option>
            <option value='Canada/Atlantic' @if (auth()->check() && 'Canada/Atlantic' === auth()->user()->timezone) selected @endif>(UTC-04:00) Atlantic Time (Canada)</option>
            <option value='America/Caracas' @if (auth()->check() && 'America/Caracas' === auth()->user()->timezone) selected @endif>(UTC-04:30) Caracas</option>
            <option value='America/La_Paz' @if (auth()->check() && 'America/La_Paz' === auth()->user()->timezone) selected @endif>(UTC-04:00) La Paz</option>
            <option value='America/Santiago' @if (auth()->check() && 'America/Santiago' === auth()->user()->timezone) selected @endif>(UTC-04:00) Santiago</option>
            <option value='Canada/Newfoundland' @if (auth()->check() && 'Canada/Newfoundland' === auth()->user()->timezone) selected @endif>(UTC-03:30) Newfoundland</option>
            <option value='America/Sao_Paulo' @if (auth()->check() && 'America/Sao_Paulo' === auth()->user()->timezone) selected @endif>(UTC-03:00) Brasilia</option>
            <option value='America/Argentina/Buenos_Aires' @if (auth()->check() && 'America/Argentina/Buenos_Aires' === auth()->user()->timezone) selected @endif>(UTC-03:00) Buenos Aires, Georgetown</option>
            <option value='America/Godthab' @if (auth()->check() && 'America/Godthab' === auth()->user()->timezone) selected @endif>(UTC-03:00) Greenland</option>
            <option value='America/Noronha' @if (auth()->check() && 'America/Noronha' === auth()->user()->timezone) selected @endif>(UTC-02:00) Mid-Atlantic</option>
            <option value='Atlantic/Azores' @if (auth()->check() && 'Atlantic/Azores' === auth()->user()->timezone) selected @endif>(UTC-01:00) Azores</option>
            <option value='Atlantic/Cape_Verde' @if (auth()->check() && 'Atlantic/Cape_Verde' === auth()->user()->timezone) selected @endif>(UTC-01:00) Cape Verde Is.</option>
            <option value='Africa/Casablanca' @if (auth()->check() && 'Africa/Casablanca' === auth()->user()->timezone) selected @endif>(UTC+00:00) Casablanca</option>
            <option value='Europe/London' @if (auth()->check() && 'Europe/London' === auth()->user()->timezone) selected @endif>(UTC+00:00) London, Edinburgh</option>
            <option value='Etc/Greenwich' @if (auth()->check() && 'Etc/Greenwich' === auth()->user()->timezone) selected @endif>(UTC+00:00) Greenwich Mean Time : Dublin</option>
            <option value='Europe/Lisbon' @if (auth()->check() && 'Europe/Lisbon' === auth()->user()->timezone) selected @endif>(UTC+00:00) Lisbon</option>
            <option value='Africa/Monrovia' @if (auth()->check() && 'Africa/Monrovia' === auth()->user()->timezone) selected @endif>(UTC+00:00) Monrovia</option>
            <option value='UTC' @if (!auth()->check() || 'UTC' === auth()->user()->timezone) selected @endif>(UTC+00:00) UTC</option>
            <option value='Europe/Amsterdam' @if (auth()->check() && 'Europe/Amsterdam' === auth()->user()->timezone) selected @endif>(UTC+01:00) Amsterdam</option>
            <option value='Europe/Belgrade' @if (auth()->check() && 'Europe/Belgrade' === auth()->user()->timezone) selected @endif>(UTC+01:00) Belgrade</option>
            <option value='Europe/Berlin' @if (auth()->check() && 'Europe/Berlin' === auth()->user()->timezone) selected @endif>(UTC+01:00) Berlin, Bern</option>
            <option value='Europe/Bratislava' @if (auth()->check() && 'Europe/Bratislava' === auth()->user()->timezone) selected @endif>(UTC+01:00) Bratislava</option>
            <option value='Europe/Brussels' @if (auth()->check() && 'Europe/Brussels' === auth()->user()->timezone) selected @endif>(UTC+01:00) Brussels</option>
            <option value='Europe/Budapest' @if (auth()->check() && 'Europe/Budapest' === auth()->user()->timezone) selected @endif>(UTC+01:00) Budapest</option>
            <option value='Europe/Copenhagen' @if (auth()->check() && 'Europe/Copenhagen' === auth()->user()->timezone) selected @endif>(UTC+01:00) Copenhagen</option>
            <option value='Europe/Ljubljana' @if (auth()->check() && 'Europe/Ljubljana' === auth()->user()->timezone) selected @endif>(UTC+01:00) Ljubljana</option>
            <option value='Europe/Madrid' @if (auth()->check() && 'Europe/Madrid' === auth()->user()->timezone) selected @endif>(UTC+01:00) Madrid</option>
            <option value='Europe/Paris' @if (auth()->check() && 'Europe/Paris' === auth()->user()->timezone) selected @endif>(UTC+01:00) Paris</option>
            <option value='Europe/Prague' @if (auth()->check() && 'Europe/Prague' === auth()->user()->timezone) selected @endif>(UTC+01:00) Prague</option>
            <option value='Europe/Rome' @if (auth()->check() && 'Europe/Rome' === auth()->user()->timezone) selected @endif>(UTC+01:00) Rome</option>
            <option value='Europe/Sarajevo' @if (auth()->check() && 'Europe/Sarajevo' === auth()->user()->timezone) selected @endif>(UTC+01:00) Sarajevo</option>
            <option value='Europe/Skopje' @if (auth()->check() && 'Europe/Skopje' === auth()->user()->timezone) selected @endif>(UTC+01:00) Skopje</option>
            <option value='Europe/Stockholm' @if (auth()->check() && 'Europe/Stockholm' === auth()->user()->timezone) selected @endif>(UTC+01:00) Stockholm</option>
            <option value='Europe/Vienna' @if (auth()->check() && 'Europe/Vienna' === auth()->user()->timezone) selected @endif>(UTC+01:00) Vienna</option>
            <option value='Europe/Warsaw' @if (auth()->check() && 'Europe/Warsaw' === auth()->user()->timezone) selected @endif>(UTC+01:00) Warsaw</option>
            <option value='Africa/Lagos' @if (auth()->check() && 'Africa/Lagos' === auth()->user()->timezone) selected @endif>(UTC+01:00) West Central Africa</option>
            <option value='Europe/Zagreb' @if (auth()->check() && 'Europe/Zagreb' === auth()->user()->timezone) selected @endif>(UTC+01:00) Zagreb</option>
            <option value='Europe/Athens' @if (auth()->check() && 'Europe/Athens' === auth()->user()->timezone) selected @endif>(UTC+02:00) Athens</option>
            <option value='Europe/Bucharest' @if (auth()->check() && 'Europe/Bucharest' === auth()->user()->timezone) selected @endif>(UTC+02:00) Bucharest</option>
            <option value='Africa/Cairo' @if (auth()->check() && 'Africa/Cairo' === auth()->user()->timezone) selected @endif>(UTC+02:00) Cairo</option>
            <option value='Africa/Harare' @if (auth()->check() && 'Africa/Harare' === auth()->user()->timezone) selected @endif>(UTC+02:00) Harare</option>
            <option value='Europe/Helsinki' @if (auth()->check() && 'Europe/Helsinki' === auth()->user()->timezone) selected @endif>(UTC+02:00) Helsinki, Kyiv</option>
            <option value='Europe/Istanbul' @if (auth()->check() && 'Europe/Istanbul' === auth()->user()->timezone) selected @endif>(UTC+02:00) Istanbul</option>
            <option value='Asia/Jerusalem' @if (auth()->check() && 'Asia/Jerusalem' === auth()->user()->timezone) selected @endif>(UTC+02:00) Jerusalem</option>
            <option value='Africa/Johannesburg' @if (auth()->check() && 'Africa/Johannesburg' === auth()->user()->timezone) selected @endif>(UTC+02:00) Pretoria</option>
            <option value='Europe/Riga' @if (auth()->check() && 'Europe/Riga' === auth()->user()->timezone) selected @endif>(UTC+02:00) Riga</option>
            <option value='Europe/Sofia' @if (auth()->check() && 'Europe/Sofia' === auth()->user()->timezone) selected @endif>(UTC+02:00) Sofia</option>
            <option value='Europe/Tallinn' @if (auth()->check() && 'Europe/Tallinn' === auth()->user()->timezone) selected @endif>(UTC+02:00) Tallinn</option>
            <option value='Europe/Vilnius' @if (auth()->check() && 'Europe/Vilnius' === auth()->user()->timezone) selected @endif>(UTC+02:00) Vilnius</option>
            <option value='Asia/Baghdad' @if (auth()->check() && 'Asia/Baghdad' === auth()->user()->timezone) selected @endif>(UTC+03:00) Baghdad</option>
            <option value='Asia/Kuwait' @if (auth()->check() && 'Asia/Kuwait' === auth()->user()->timezone) selected @endif>(UTC+03:00) Kuwait</option>
            <option value='Europe/Minsk' @if (auth()->check() && 'Europe/Minsk' === auth()->user()->timezone) selected @endif>(UTC+03:00) Minsk</option>
            <option value='Africa/Nairobi' @if (auth()->check() && 'Africa/Nairobi' === auth()->user()->timezone) selected @endif>(UTC+03:00) Nairobi</option>
            <option value='Asia/Riyadh' @if (auth()->check() && 'Asia/Riyadh' === auth()->user()->timezone) selected @endif>(UTC+03:00) Riyadh</option>
            <option value='Europe/Volgograd' @if (auth()->check() && 'Europe/Volgograd' === auth()->user()->timezone) selected @endif>(UTC+03:00) Volgograd</option>
            <option value='Asia/Tehran' @if (auth()->check() && 'Asia/Tehran' === auth()->user()->timezone) selected @endif>(UTC+03:30) Tehran</option>
            <option value='Asia/Muscat' @if (auth()->check() && 'Asia/Muscat' === auth()->user()->timezone) selected @endif>(UTC+04:00) Muscat, Abu Dhabi</option>
            <option value='Asia/Baku' @if (auth()->check() && 'Asia/Baku' === auth()->user()->timezone) selected @endif>(UTC+04:00) Baku</option>
            <option value='Europe/Moscow' @if (auth()->check() && 'Europe/Moscow' === auth()->user()->timezone) selected @endif>(UTC+04:00) Moscow, St. Petersburg</option>
            <option value='Asia/Tbilisi' @if (auth()->check() && 'Asia/Tbilisi' === auth()->user()->timezone) selected @endif>(UTC+04:00) Tbilisi</option>
            <option value='Asia/Yerevan' @if (auth()->check() && 'Asia/Yerevan' === auth()->user()->timezone) selected @endif>(UTC+04:00) Yerevan</option>
            <option value='Asia/Kabul' @if (auth()->check() && 'Asia/Kabul' === auth()->user()->timezone) selected @endif>(UTC+04:30) Kabul</option>
            <option value='Asia/Karachi' @if (auth()->check() && 'Asia/Karachi' === auth()->user()->timezone) selected @endif>(UTC+05:00) Karachi, Islamabad</option>
            <option value='Asia/Tashkent' @if (auth()->check() && 'Asia/Tashkent' === auth()->user()->timezone) selected @endif>(UTC+05:00) Tashkent</option>
            <option value='Asia/Kolkata' @if (auth()->check() && 'Asia/Kolkata' === auth()->user()->timezone) selected @endif>(UTC+05:30) Kolkata, Chennai, Mumbai, New Delhi, Sri Jayawardenepura</option>
            <option value='Asia/Katmandu' @if (auth()->check() && 'Asia/Katmandu' === auth()->user()->timezone) selected @endif>(UTC+05:45) Kathmandu</option>
            <option value='Asia/Almaty' @if (auth()->check() && 'Asia/Almaty' === auth()->user()->timezone) selected @endif>(UTC+06:00) Almaty</option>
            <option value='Asia/Dhaka' @if (auth()->check() && 'Asia/Dhaka' === auth()->user()->timezone) selected @endif>(UTC+06:00) Dhaka, Astana</option>
            <option value='Asia/Yekaterinburg' @if (auth()->check() && 'Asia/Yekaterinburg' === auth()->user()->timezone) selected @endif>(UTC+06:00) Ekaterinburg</option>
            <option value='Asia/Rangoon' @if (auth()->check() && 'Asia/Rangoon' === auth()->user()->timezone) selected @endif>(UTC+06:30) Rangoon</option>
            <option value='Asia/Bangkok' @if (auth()->check() && 'Asia/Bangkok' === auth()->user()->timezone) selected @endif>(UTC+07:00) Bangkok, Hanoi</option>
            <option value='Asia/Jakarta' @if (auth()->check() && 'Asia/Jakarta' === auth()->user()->timezone) selected @endif>(UTC+07:00) Jakarta</option>
            <option value='Asia/Novosibirsk' @if (auth()->check() && 'Asia/Novosibirsk' === auth()->user()->timezone) selected @endif>(UTC+07:00) Novosibirsk</option>
            <option value='Asia/Chongqing' @if (auth()->check() && 'Asia/Chongqing' === auth()->user()->timezone) selected @endif>(UTC+08:00) Chongqing</option>
            <option value='Asia/Hong_Kong' @if (auth()->check() && 'Asia/Hong_Kong' === auth()->user()->timezone) selected @endif>(UTC+08:00) Hong Kong, Beijing</option>
            <option value='Asia/Krasnoyarsk' @if (auth()->check() && 'Asia/Krasnoyarsk' === auth()->user()->timezone) selected @endif>(UTC+08:00) Krasnoyarsk</option>
            <option value='Asia/Kuala_Lumpur' @if (auth()->check() && 'Asia/Kuala_Lumpur' === auth()->user()->timezone) selected @endif>(UTC+08:00) Kuala Lumpur</option>
            <option value='Australia/Perth' @if (auth()->check() && 'Australia/Perth' === auth()->user()->timezone) selected @endif>(UTC+08:00) Perth</option>
            <option value='Asia/Singapore' @if (auth()->check() && 'Asia/Singapore' === auth()->user()->timezone) selected @endif>(UTC+08:00) Singapore</option>
            <option value='Asia/Taipei' @if (auth()->check() && 'Asia/Taipei' === auth()->user()->timezone) selected @endif>(UTC+08:00) Taipei</option>
            <option value='Asia/Ulan_Bator' @if (auth()->check() && 'Asia/Ulan_Bator' === auth()->user()->timezone) selected @endif>(UTC+08:00) Ulaan Bataar</option>
            <option value='Asia/Urumqi' @if (auth()->check() && 'Asia/Urumqi' === auth()->user()->timezone) selected @endif>(UTC+08:00) Urumqi</option>
            <option value='Asia/Irkutsk' @if (auth()->check() && 'Asia/Irkutsk' === auth()->user()->timezone) selected @endif>(UTC+09:00) Irkutsk</option>
            <option value='Asia/Tokyo' @if (auth()->check() && 'Asia/Tokyo' === auth()->user()->timezone) selected @endif>(UTC+09:00) Tokyo, Osaka, Sapporo</option>
            <option value='Asia/Seoul' @if (auth()->check() && 'Asia/Seoul' === auth()->user()->timezone) selected @endif>(UTC+09:00) Seoul</option>
            <option value='Australia/Adelaide' @if (auth()->check() && 'Australia/Adelaide' === auth()->user()->timezone) selected @endif>(UTC+09:30) Adelaide</option>
            <option value='Australia/Darwin' @if (auth()->check() && 'Australia/Darwin' === auth()->user()->timezone) selected @endif>(UTC+09:30) Darwin</option>
            <option value='Australia/Brisbane' @if (auth()->check() && 'Australia/Brisbane' === auth()->user()->timezone) selected @endif>(UTC+10:00) Brisbane</option>
            <option value='Australia/Canberra' @if (auth()->check() && 'Australia/Canberra' === auth()->user()->timezone) selected @endif>(UTC+10:00) Canberra</option>
            <option value='Pacific/Guam' @if (auth()->check() && 'Pacific/Guam' === auth()->user()->timezone) selected @endif>(UTC+10:00) Guam</option>
            <option value='Australia/Hobart' @if (auth()->check() && 'Australia/Hobart' === auth()->user()->timezone) selected @endif>(UTC+10:00) Hobart</option>
            <option value='Australia/Melbourne' @if (auth()->check() && 'Australia/Melbourne' === auth()->user()->timezone) selected @endif>(UTC+10:00) Melbourne</option>
            <option value='Pacific/Port_Moresby' @if (auth()->check() && 'Pacific/Port_Moresby' === auth()->user()->timezone) selected @endif>(UTC+10:00) Port Moresby</option>
            <option value='Australia/Sydney' @if (auth()->check() && 'Australia/Sydney' === auth()->user()->timezone) selected @endif>(UTC+10:00) Sydney</option>
            <option value='Asia/Yakutsk' @if (auth()->check() && 'Asia/Yakutsk' === auth()->user()->timezone) selected @endif>(UTC+10:00) Yakutsk</option>
            <option value='Asia/Vladivostok' @if (auth()->check() && 'Asia/Vladivostok' === auth()->user()->timezone) selected @endif>(UTC+11:00) Vladivostok</option>
            <option value='Pacific/Auckland' @if (auth()->check() && 'Pacific/Auckland' === auth()->user()->timezone) selected @endif>(UTC+12:00) Auckland, Wellington</option>
            <option value='Pacific/Fiji' @if (auth()->check() && 'Pacific/Fiji' === auth()->user()->timezone) selected @endif>(UTC+12:00) Fiji, Marshall Is.</option>
            <option value='Pacific/Kwajalein' @if (auth()->check() && 'Pacific/Kwajalein' === auth()->user()->timezone) selected @endif>(UTC+12:00) International Date Line West</option>
            <option value='Asia/Kamchatka' @if (auth()->check() && 'Asia/Kamchatka' === auth()->user()->timezone) selected @endif>(UTC+12:00) Kamchatka</option>
            <option value='Asia/Magadan' @if (auth()->check() && 'Asia/Magadan' === auth()->user()->timezone) selected @endif>(UTC+12:00) Magadan, New Caledonia, Solomon Is.</option>
            <option value='Pacific/Tongatapu' @if (auth()->check() && 'Pacific/Tongatapu' === auth()->user()->timezone) selected @endif>(UTC+13:00) Nuku'alofa</option>
        </select>

        @if ($errors->has('timezone'))
            <div class="invalid-feedback">
                <strong>{{ $errors->first('timezone') }}</strong>
            </div>
        @endif
    </div>
</div>
