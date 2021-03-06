<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PrivateMessages\Thread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ThreadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $thread = new Thread();
        $thread->user_id = User::firstOrFail()->id;
        $thread->subject = 'Test';
        $thread->save();

        $thread->participants()->createMany([
            ['user_id' => 1, 'last_read_at' => Carbon::now()->subDay()],
            ['user_id' => 2, 'last_read_at' => Carbon::now()->addDays(7)],
        ]);

        $thread->messages()->create(['user_id' => 1, 'message' => 'Test message']);
    }
}
