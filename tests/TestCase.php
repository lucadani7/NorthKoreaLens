<?php

namespace Tests;

use App\Jobs\GeneratePostAudio;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Queue;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['scout.driver' => 'null']);
        Queue::fake([GeneratePostAudio::class]);

        // Tests check what the server sends, not the built JS/CSS, so they must not depend
        // on a Vite manifest that may be stale or missing (for example in CI).
        $this->withoutVite();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
