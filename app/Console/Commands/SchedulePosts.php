<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SchedulePosts extends Command
{
    protected $signature = 'posts:schedule
                            {category : Section slug, e.g. curiosities}
                            {--per-day=10 : Posts a day}
                            {--at=10:00 : Time of the first post each day; the others follow a minute apart}
                            {--timezone=Europe/Bucharest : Time zone of --at}
                            {--dry-run : Only list the dates}';

    protected $description = "Give the team's drafts in a section future publication dates, a few a day, after those already scheduled";

    public function handle(): int
    {
        $category = Category::where('slug', $this->argument('category'))->first();
        $perDay = (int) $this->option('per-day');
        $timezone = (string) $this->option('timezone');

        if ($category === null) {
            $this->error('No section with that slug.');

            return self::FAILURE;
        }

        if ($perDay < 1 || preg_match('/\A([01]?\d|2[0-3]):([0-5]\d)\z/', (string) $this->option('at'), $at) !== 1
            || ! in_array($timezone, timezone_identifiers_list(), true)) {
            $this->error('Use --per-day=1 or more, --at=HH:MM and a time zone such as Europe/Bucharest.');

            return self::FAILURE;
        }

        // Only the team's own drafts: drafts from feeds wait for someone to review them.
        $drafts = Post::query()
            ->whereBelongsTo($category)
            ->whereNull('source_id')
            ->whereNull('published_at')
            ->orderBy('id')
            ->get();

        if ($drafts->isEmpty()) {
            $this->info('No drafts to schedule.');

            return self::SUCCESS;
        }

        $now = CarbonImmutable::now($timezone);
        $day = $now->setTime((int) $at[1], (int) $at[2]);

        if ($day->lte($now)) {
            $day = $day->addDay();
        }

        // Continue after the posts already scheduled in this section. Dates are stored in UTC,
        // so every date given to the database is converted first.
        $used = 0;
        $last = Post::query()->whereBelongsTo($category)->where('published_at', '>', $now->utc())->max('published_at');

        if ($last !== null) {
            $lastDay = CarbonImmutable::parse($last)->setTimezone($timezone)->setTime((int) $at[1], (int) $at[2]);

            if ($lastDay->gte($day)) {
                $day = $lastDay;
                $used = Post::query()->whereBelongsTo($category)
                    ->where('published_at', '>=', $lastDay->startOfDay()->utc())
                    ->where('published_at', '<', $lastDay->startOfDay()->addDay()->utc())
                    ->count();
            }
        }

        $dryRun = (bool) $this->option('dry-run');

        foreach ($drafts as $post) {
            if ($used >= $perDay) {
                $day = $day->addDay();
                $used = 0;
            }

            $date = $day->addMinutes($used++);
            $this->line($date->format('Y-m-d H:i').'  '.$post->title);

            if (! $dryRun) {
                $post->update(['published_at' => $date->utc()]);
            }
        }

        $this->info(sprintf(
            '%s %d posts, until %s (%s).',
            $dryRun ? 'Would schedule' : 'Scheduled',
            $drafts->count(),
            $day->format('Y-m-d'),
            $timezone,
        ));

        return self::SUCCESS;
    }
}
