<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * @return Response
     */
    public function index(): Response
    {
        $a = DB::table('users')->where('passkey', '=', '0198a7b954d5aa46bb94d3fe532527c8aebc962b0a06090123d61db82bcb1050')
            ->select(['id', 'slug', 'uploaded', 'downloaded'])
            ->first();
        $a->uploaded = $a->uploaded + 200;
        $a->uploaded = $a->uploaded + 300;
        dd($a);
        /*dd(DB::table('users')->where('passkey', '=', '0198a7b954d5aa46bb94d3fe532527c8aebc962b0a06090123d61db82bcb1050')
            ->select(['id', 'slug', 'uploaded', 'downloaded'])
            ->first());*/
        //dd(DB::getQueryGrammar()->getDateFormat());
        $x = DB::table('users')->first();
        $s = $x->created_at;
        dd(Carbon::createFromFormat(
            str_replace('.v', '.u', DB::getQueryGrammar()->getDateFormat()), $s, 'UTC'
        ));
        dd((new Carbon($s))->toDateTimeString());
        dd(Carbon::createFromFormat('Y-m-d H:i:s', $s)->toDateTimeString());
        dd(DB::table('users')->first()->created_at);
        return response()->view('home.index');
    }
}
