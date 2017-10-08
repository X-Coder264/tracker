<div class="form-group">
    <label for="timezone" class="col control-label">Timezone</label>
    <div class="col">
        <select class="form-control" name="timezone" id="timezone" required>
            <option value='Pacific/Midway' @if(Auth::check() && 'Pacific/Midway' === Auth::user()->timezone) selected @endif>(UTC-11:00) Midway Island</option>
            <option value='Pacific/Samoa' @if(Auth::check() && 'Pacific/Samoa' === Auth::user()->timezone) selected @endif>(UTC-11:00) Samoa</option>
            <option value='Pacific/Honolulu' @if(Auth::check() && 'Pacific/Honolulu' === Auth::user()->timezone) selected @endif>(UTC-10:00) Hawaii</option>
            <option value='US/Alaska' @if(Auth::check() && 'US/Alaska' === Auth::user()->timezone) selected @endif>(UTC-09:00) Alaska</option>
            <option value='America/Los_Angeles' @if(Auth::check() && 'America/Los_Angeles' === Auth::user()->timezone) selected @endif>(UTC-08:00) Pacific Time (US &amp; Canada)</option>
            <option value='America/Tijuana' @if(Auth::check() && 'America/Tijuana' === Auth::user()->timezone) selected @endif>(UTC-08:00) Tijuana</option>
            <option value='US/Arizona' @if(Auth::check() && 'US/Arizona' === Auth::user()->timezone) selected @endif>(UTC-07:00) Arizona</option>
            <option value='America/Chihuahua' @if(Auth::check() && 'America/Chihuahua' === Auth::user()->timezone) selected @endif>(UTC-07:00) Chihuahua, La Paz</option>
            <option value='America/Mazatlan' @if(Auth::check() && 'America/Mazatlan' === Auth::user()->timezone) selected @endif>(UTC-07:00) Mazatlan</option>
            <option value='US/Mountain' @if(Auth::check() && 'US/Mountain' === Auth::user()->timezone) selected @endif>(UTC-07:00) Mountain Time (US &amp; Canada)</option>
            <option value='America/Managua' @if(Auth::check() && 'America/Managua' === Auth::user()->timezone) selected @endif>(UTC-06:00) Central America</option>
            <option value='US/Central' @if(Auth::check() && 'US/Central' === Auth::user()->timezone) selected @endif>(UTC-06:00) Central Time (US &amp; Canada)</option>
            <option value='America/Mexico_City' @if(Auth::check() && 'America/Mexico_City' === Auth::user()->timezone) selected @endif>(UTC-06:00) Mexico City, Guadalajara</option>
            <option value='America/Monterrey' @if(Auth::check() && 'America/Monterrey' === Auth::user()->timezone) selected @endif>(UTC-06:00) Monterrey</option>
            <option value='Canada/Saskatchewan' @if(Auth::check() && 'Canada/Saskatchewan' === Auth::user()->timezone) selected @endif>(UTC-06:00) Saskatchewan</option>
            <option value='America/Bogota' @if(Auth::check() && 'America/Bogota' === Auth::user()->timezone) selected @endif>(UTC-05:00) Bogota, Quito</option>
            <option value='US/Eastern' @if(Auth::check() && 'US/Eastern' === Auth::user()->timezone) selected @endif>(UTC-05:00) Eastern Time (US &amp; Canada)</option>
            <option value='US/East-Indiana' @if(Auth::check() && 'US/East-Indiana' === Auth::user()->timezone) selected @endif>(UTC-05:00) Indiana (East)</option>
            <option value='America/Lima' @if(Auth::check() && 'America/Lima' === Auth::user()->timezone) selected @endif>(UTC-05:00) Lima</option>
            <option value='Canada/Atlantic' @if(Auth::check() && 'Canada/Atlantic' === Auth::user()->timezone) selected @endif>(UTC-04:00) Atlantic Time (Canada)</option>
            <option value='America/Caracas' @if(Auth::check() && 'America/Caracas' === Auth::user()->timezone) selected @endif>(UTC-04:30) Caracas</option>
            <option value='America/La_Paz' @if(Auth::check() && 'America/La_Paz' === Auth::user()->timezone) selected @endif>(UTC-04:00) La Paz</option>
            <option value='America/Santiago' @if(Auth::check() && 'America/Santiago' === Auth::user()->timezone) selected @endif>(UTC-04:00) Santiago</option>
            <option value='Canada/Newfoundland' @if(Auth::check() && 'Canada/Newfoundland' === Auth::user()->timezone) selected @endif>(UTC-03:30) Newfoundland</option>
            <option value='America/Sao_Paulo' @if(Auth::check() && 'America/Sao_Paulo' === Auth::user()->timezone) selected @endif>(UTC-03:00) Brasilia</option>
            <option value='America/Argentina/Buenos_Aires' @if(Auth::check() && 'America/Argentina/Buenos_Aires' === Auth::user()->timezone) selected @endif>(UTC-03:00) Buenos Aires, Georgetown</option>
            <option value='America/Godthab' @if(Auth::check() && 'America/Godthab' === Auth::user()->timezone) selected @endif>(UTC-03:00) Greenland</option>
            <option value='America/Noronha' @if(Auth::check() && 'America/Noronha' === Auth::user()->timezone) selected @endif>(UTC-02:00) Mid-Atlantic</option>
            <option value='Atlantic/Azores' @if(Auth::check() && 'Atlantic/Azores' === Auth::user()->timezone) selected @endif>(UTC-01:00) Azores</option>
            <option value='Atlantic/Cape_Verde' @if(Auth::check() && 'Atlantic/Cape_Verde' === Auth::user()->timezone) selected @endif>(UTC-01:00) Cape Verde Is.</option>
            <option value='Africa/Casablanca' @if(Auth::check() && 'Africa/Casablanca' === Auth::user()->timezone) selected @endif>(UTC+00:00) Casablanca</option>
            <option value='Europe/London' @if(Auth::check() && 'Europe/London' === Auth::user()->timezone) selected @endif>(UTC+00:00) London, Edinburgh</option>
            <option value='Etc/Greenwich' @if(Auth::check() && 'Etc/Greenwich' === Auth::user()->timezone) selected @endif>(UTC+00:00) Greenwich Mean Time : Dublin</option>
            <option value='Europe/Lisbon' @if(Auth::check() && 'Europe/Lisbon' === Auth::user()->timezone) selected @endif>(UTC+00:00) Lisbon</option>
            <option value='Africa/Monrovia' @if(Auth::check() && 'Africa/Monrovia' === Auth::user()->timezone) selected @endif>(UTC+00:00) Monrovia</option>
            <option value='UTC' @if(!Auth::check() || 'UTC' === Auth::user()->timezone) selected @endif>(UTC+00:00) UTC</option>
            <option value='Europe/Amsterdam' @if(Auth::check() && 'Europe/Amsterdam' === Auth::user()->timezone) selected @endif>(UTC+01:00) Amsterdam</option>
            <option value='Europe/Belgrade' @if(Auth::check() && 'Europe/Belgrade' === Auth::user()->timezone) selected @endif>(UTC+01:00) Belgrade</option>
            <option value='Europe/Berlin' @if(Auth::check() && 'Europe/Berlin' === Auth::user()->timezone) selected @endif>(UTC+01:00) Berlin, Bern</option>
            <option value='Europe/Bratislava' @if(Auth::check() && 'Europe/Bratislava' === Auth::user()->timezone) selected @endif>(UTC+01:00) Bratislava</option>
            <option value='Europe/Brussels' @if(Auth::check() && 'Europe/Brussels' === Auth::user()->timezone) selected @endif>(UTC+01:00) Brussels</option>
            <option value='Europe/Budapest' @if(Auth::check() && 'Europe/Budapest' === Auth::user()->timezone) selected @endif>(UTC+01:00) Budapest</option>
            <option value='Europe/Copenhagen' @if(Auth::check() && 'Europe/Copenhagen' === Auth::user()->timezone) selected @endif>(UTC+01:00) Copenhagen</option>
            <option value='Europe/Ljubljana' @if(Auth::check() && 'Europe/Ljubljana' === Auth::user()->timezone) selected @endif>(UTC+01:00) Ljubljana</option>
            <option value='Europe/Madrid' @if(Auth::check() && 'Europe/Madrid' === Auth::user()->timezone) selected @endif>(UTC+01:00) Madrid</option>
            <option value='Europe/Paris' @if(Auth::check() && 'Europe/Paris' === Auth::user()->timezone) selected @endif>(UTC+01:00) Paris</option>
            <option value='Europe/Prague' @if(Auth::check() && 'Europe/Prague' === Auth::user()->timezone) selected @endif>(UTC+01:00) Prague</option>
            <option value='Europe/Rome' @if(Auth::check() && 'Europe/Rome' === Auth::user()->timezone) selected @endif>(UTC+01:00) Rome</option>
            <option value='Europe/Sarajevo' @if(Auth::check() && 'Europe/Sarajevo' === Auth::user()->timezone) selected @endif>(UTC+01:00) Sarajevo</option>
            <option value='Europe/Skopje' @if(Auth::check() && 'Europe/Skopje' === Auth::user()->timezone) selected @endif>(UTC+01:00) Skopje</option>
            <option value='Europe/Stockholm' @if(Auth::check() && 'Europe/Stockholm' === Auth::user()->timezone) selected @endif>(UTC+01:00) Stockholm</option>
            <option value='Europe/Vienna' @if(Auth::check() && 'Europe/Vienna' === Auth::user()->timezone) selected @endif>(UTC+01:00) Vienna</option>
            <option value='Europe/Warsaw' @if(Auth::check() && 'Europe/Warsaw' === Auth::user()->timezone) selected @endif>(UTC+01:00) Warsaw</option>
            <option value='Africa/Lagos' @if(Auth::check() && 'Africa/Lagos' === Auth::user()->timezone) selected @endif>(UTC+01:00) West Central Africa</option>
            <option value='Europe/Zagreb' @if(Auth::check() && 'Europe/Zagreb' === Auth::user()->timezone) selected @endif>(UTC+01:00) Zagreb</option>
            <option value='Europe/Athens' @if(Auth::check() && 'Europe/Athens' === Auth::user()->timezone) selected @endif>(UTC+02:00) Athens</option>
            <option value='Europe/Bucharest' @if(Auth::check() && 'Europe/Bucharest' === Auth::user()->timezone) selected @endif>(UTC+02:00) Bucharest</option>
            <option value='Africa/Cairo' @if(Auth::check() && 'Africa/Cairo' === Auth::user()->timezone) selected @endif>(UTC+02:00) Cairo</option>
            <option value='Africa/Harare' @if(Auth::check() && 'Africa/Harare' === Auth::user()->timezone) selected @endif>(UTC+02:00) Harare</option>
            <option value='Europe/Helsinki' @if(Auth::check() && 'Europe/Helsinki' === Auth::user()->timezone) selected @endif>(UTC+02:00) Helsinki, Kyiv</option>
            <option value='Europe/Istanbul' @if(Auth::check() && 'Europe/Istanbul' === Auth::user()->timezone) selected @endif>(UTC+02:00) Istanbul</option>
            <option value='Asia/Jerusalem' @if(Auth::check() && 'Asia/Jerusalem' === Auth::user()->timezone) selected @endif>(UTC+02:00) Jerusalem</option>
            <option value='Africa/Johannesburg' @if(Auth::check() && 'Africa/Johannesburg' === Auth::user()->timezone) selected @endif>(UTC+02:00) Pretoria</option>
            <option value='Europe/Riga' @if(Auth::check() && 'Europe/Riga' === Auth::user()->timezone) selected @endif>(UTC+02:00) Riga</option>
            <option value='Europe/Sofia' @if(Auth::check() && 'Europe/Sofia' === Auth::user()->timezone) selected @endif>(UTC+02:00) Sofia</option>
            <option value='Europe/Tallinn' @if(Auth::check() && 'Europe/Tallinn' === Auth::user()->timezone) selected @endif>(UTC+02:00) Tallinn</option>
            <option value='Europe/Vilnius' @if(Auth::check() && 'Europe/Vilnius' === Auth::user()->timezone) selected @endif>(UTC+02:00) Vilnius</option>
            <option value='Asia/Baghdad' @if(Auth::check() && 'Asia/Baghdad' === Auth::user()->timezone) selected @endif>(UTC+03:00) Baghdad</option>
            <option value='Asia/Kuwait' @if(Auth::check() && 'Asia/Kuwait' === Auth::user()->timezone) selected @endif>(UTC+03:00) Kuwait</option>
            <option value='Europe/Minsk' @if(Auth::check() && 'Europe/Minsk' === Auth::user()->timezone) selected @endif>(UTC+03:00) Minsk</option>
            <option value='Africa/Nairobi' @if(Auth::check() && 'Africa/Nairobi' === Auth::user()->timezone) selected @endif>(UTC+03:00) Nairobi</option>
            <option value='Asia/Riyadh' @if(Auth::check() && 'Asia/Riyadh' === Auth::user()->timezone) selected @endif>(UTC+03:00) Riyadh</option>
            <option value='Europe/Volgograd' @if(Auth::check() && 'Europe/Volgograd' === Auth::user()->timezone) selected @endif>(UTC+03:00) Volgograd</option>
            <option value='Asia/Tehran' @if(Auth::check() && 'Asia/Tehran' === Auth::user()->timezone) selected @endif>(UTC+03:30) Tehran</option>
            <option value='Asia/Muscat' @if(Auth::check() && 'Asia/Muscat' === Auth::user()->timezone) selected @endif>(UTC+04:00) Muscat, Abu Dhabi</option>
            <option value='Asia/Baku' @if(Auth::check() && 'Asia/Baku' === Auth::user()->timezone) selected @endif>(UTC+04:00) Baku</option>
            <option value='Europe/Moscow' @if(Auth::check() && 'Europe/Moscow' === Auth::user()->timezone) selected @endif>(UTC+04:00) Moscow, St. Petersburg</option>
            <option value='Asia/Tbilisi' @if(Auth::check() && 'Asia/Tbilisi' === Auth::user()->timezone) selected @endif>(UTC+04:00) Tbilisi</option>
            <option value='Asia/Yerevan' @if(Auth::check() && 'Asia/Yerevan' === Auth::user()->timezone) selected @endif>(UTC+04:00) Yerevan</option>
            <option value='Asia/Kabul' @if(Auth::check() && 'Asia/Kabul' === Auth::user()->timezone) selected @endif>(UTC+04:30) Kabul</option>
            <option value='Asia/Karachi' @if(Auth::check() && 'Asia/Karachi' === Auth::user()->timezone) selected @endif>(UTC+05:00) Karachi, Islamabad</option>
            <option value='Asia/Tashkent' @if(Auth::check() && 'Asia/Tashkent' === Auth::user()->timezone) selected @endif>(UTC+05:00) Tashkent</option>
            <option value='Asia/Kolkata' @if(Auth::check() && 'Asia/Kolkata' === Auth::user()->timezone) selected @endif>(UTC+05:30) Kolkata, Chennai, Mumbai, New Delhi, Sri Jayawardenepura</option>
            <option value='Asia/Katmandu' @if(Auth::check() && 'Asia/Katmandu' === Auth::user()->timezone) selected @endif>(UTC+05:45) Kathmandu</option>
            <option value='Asia/Almaty' @if(Auth::check() && 'Asia/Almaty' === Auth::user()->timezone) selected @endif>(UTC+06:00) Almaty</option>
            <option value='Asia/Dhaka' @if(Auth::check() && 'Asia/Dhaka' === Auth::user()->timezone) selected @endif>(UTC+06:00) Dhaka, Astana</option>
            <option value='Asia/Yekaterinburg' @if(Auth::check() && 'Asia/Yekaterinburg' === Auth::user()->timezone) selected @endif>(UTC+06:00) Ekaterinburg</option>
            <option value='Asia/Rangoon' @if(Auth::check() && 'Asia/Rangoon' === Auth::user()->timezone) selected @endif>(UTC+06:30) Rangoon</option>
            <option value='Asia/Bangkok' @if(Auth::check() && 'Asia/Bangkok' === Auth::user()->timezone) selected @endif>(UTC+07:00) Bangkok, Hanoi</option>
            <option value='Asia/Jakarta' @if(Auth::check() && 'Asia/Jakarta' === Auth::user()->timezone) selected @endif>(UTC+07:00) Jakarta</option>
            <option value='Asia/Novosibirsk' @if(Auth::check() && 'Asia/Novosibirsk' === Auth::user()->timezone) selected @endif>(UTC+07:00) Novosibirsk</option>
            <option value='Asia/Chongqing' @if(Auth::check() && 'Asia/Chongqing' === Auth::user()->timezone) selected @endif>(UTC+08:00) Chongqing</option>
            <option value='Asia/Hong_Kong' @if(Auth::check() && 'Asia/Hong_Kong' === Auth::user()->timezone) selected @endif>(UTC+08:00) Hong Kong, Beijing</option>
            <option value='Asia/Krasnoyarsk' @if(Auth::check() && 'Asia/Krasnoyarsk' === Auth::user()->timezone) selected @endif>(UTC+08:00) Krasnoyarsk</option>
            <option value='Asia/Kuala_Lumpur' @if(Auth::check() && 'Asia/Kuala_Lumpur' === Auth::user()->timezone) selected @endif>(UTC+08:00) Kuala Lumpur</option>
            <option value='Australia/Perth' @if(Auth::check() && 'Australia/Perth' === Auth::user()->timezone) selected @endif>(UTC+08:00) Perth</option>
            <option value='Asia/Singapore' @if(Auth::check() && 'Asia/Singapore' === Auth::user()->timezone) selected @endif>(UTC+08:00) Singapore</option>
            <option value='Asia/Taipei' @if(Auth::check() && 'Asia/Taipei' === Auth::user()->timezone) selected @endif>(UTC+08:00) Taipei</option>
            <option value='Asia/Ulan_Bator' @if(Auth::check() && 'Asia/Ulan_Bator' === Auth::user()->timezone) selected @endif>(UTC+08:00) Ulaan Bataar</option>
            <option value='Asia/Urumqi' @if(Auth::check() && 'Asia/Urumqi' === Auth::user()->timezone) selected @endif>(UTC+08:00) Urumqi</option>
            <option value='Asia/Irkutsk' @if(Auth::check() && 'Asia/Irkutsk' === Auth::user()->timezone) selected @endif>(UTC+09:00) Irkutsk</option>
            <option value='Asia/Tokyo' @if(Auth::check() && 'Asia/Tokyo' === Auth::user()->timezone) selected @endif>(UTC+09:00) Tokyo, Osaka, Sapporo</option>
            <option value='Asia/Seoul' @if(Auth::check() && 'Asia/Seoul' === Auth::user()->timezone) selected @endif>(UTC+09:00) Seoul</option>
            <option value='Australia/Adelaide' @if(Auth::check() && 'Australia/Adelaide' === Auth::user()->timezone) selected @endif>(UTC+09:30) Adelaide</option>
            <option value='Australia/Darwin' @if(Auth::check() && 'Australia/Darwin' === Auth::user()->timezone) selected @endif>(UTC+09:30) Darwin</option>
            <option value='Australia/Brisbane' @if(Auth::check() && 'Australia/Brisbane' === Auth::user()->timezone) selected @endif>(UTC+10:00) Brisbane</option>
            <option value='Australia/Canberra' @if(Auth::check() && 'Australia/Canberra' === Auth::user()->timezone) selected @endif>(UTC+10:00) Canberra</option>
            <option value='Pacific/Guam' @if(Auth::check() && 'Pacific/Guam' === Auth::user()->timezone) selected @endif>(UTC+10:00) Guam</option>
            <option value='Australia/Hobart' @if(Auth::check() && 'Australia/Hobart' === Auth::user()->timezone) selected @endif>(UTC+10:00) Hobart</option>
            <option value='Australia/Melbourne' @if(Auth::check() && 'Australia/Melbourne' === Auth::user()->timezone) selected @endif>(UTC+10:00) Melbourne</option>
            <option value='Pacific/Port_Moresby' @if(Auth::check() && 'Pacific/Port_Moresby' === Auth::user()->timezone) selected @endif>(UTC+10:00) Port Moresby</option>
            <option value='Australia/Sydney' @if(Auth::check() && 'Australia/Sydney' === Auth::user()->timezone) selected @endif>(UTC+10:00) Sydney</option>
            <option value='Asia/Yakutsk' @if(Auth::check() && 'Asia/Yakutsk' === Auth::user()->timezone) selected @endif>(UTC+10:00) Yakutsk</option>
            <option value='Asia/Vladivostok' @if(Auth::check() && 'Asia/Vladivostok' === Auth::user()->timezone) selected @endif>(UTC+11:00) Vladivostok</option>
            <option value='Pacific/Auckland' @if(Auth::check() && 'Pacific/Auckland' === Auth::user()->timezone) selected @endif>(UTC+12:00) Auckland, Wellington</option>
            <option value='Pacific/Fiji' @if(Auth::check() && 'Pacific/Fiji' === Auth::user()->timezone) selected @endif>(UTC+12:00) Fiji, Marshall Is.</option>
            <option value='Pacific/Kwajalein' @if(Auth::check() && 'Pacific/Kwajalein' === Auth::user()->timezone) selected @endif>(UTC+12:00) International Date Line West</option>
            <option value='Asia/Kamchatka' @if(Auth::check() && 'Asia/Kamchatka' === Auth::user()->timezone) selected @endif>(UTC+12:00) Kamchatka</option>
            <option value='Asia/Magadan' @if(Auth::check() && 'Asia/Magadan' === Auth::user()->timezone) selected @endif>(UTC+12:00) Magadan, New Caledonia, Solomon Is.</option>
            <option value='Pacific/Tongatapu' @if(Auth::check() && 'Pacific/Tongatapu' === Auth::user()->timezone) selected @endif>(UTC+13:00) Nuku'alofa</option>
        </select>
    </div>
</div>
