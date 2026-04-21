<?php

use App\Models\BlogPost;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('posts:publish-scheduled', function () {
    $posts = BlogPost::where('status', 'scheduled')
        ->where('scheduled_at', '<=', now())
        ->get();

    $count = 0;
    foreach ($posts as $post) {
        $post->update([
            'status' => 'published',
            'published_at' => $post->scheduled_at,
        ]);
        $count++;
    }

    $this->info("Published {$count} scheduled post(s).");
})->purpose('Publish blog posts that have reached their scheduled date');

Schedule::command('posts:publish-scheduled')->everyMinute();
