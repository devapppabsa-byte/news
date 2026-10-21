<?php

namespace Database\Seeders;

use App\Models\MonitoredTopic;
use App\Models\NewsItem;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $editor = User::factory()->create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
        ]);

        $editor->preference()->create();

        $topic = MonitoredTopic::factory()->for($editor)->create([
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        NewsItem::factory()->published()->forTopic($topic)->create();
        Subscription::factory()->for($admin)->for($topic)->create();
    }
}
