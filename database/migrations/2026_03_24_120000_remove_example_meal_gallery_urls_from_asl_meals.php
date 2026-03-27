<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PLACEHOLDER_GALLERY_URLS = [
        'https://example.com/images/meals/gallery-1.jpg',
        'https://example.com/images/meals/gallery-2.jpg',
    ];

    /**
     * Remove seeded placeholder gallery URLs from meal `images` JSON (not real files on disk).
     */
    public function up(): void
    {
        $remove = self::PLACEHOLDER_GALLERY_URLS;

        DB::table('asl_meals')
            ->whereNotNull('images')
            ->orderBy('id')
            ->chunk(100, function ($rows) use ($remove): void {
                foreach ($rows as $row) {
                    $images = json_decode($row->images, true);
                    if (! is_array($images)) {
                        continue;
                    }

                    $filtered = array_values(array_filter(
                        $images,
                        static fn (mixed $u): bool => is_string($u) && ! in_array($u, $remove, true)
                    ));

                    if (count($filtered) === count($images)) {
                        continue;
                    }

                    DB::table('asl_meals')->where('id', $row->id)->update([
                        'images' => json_encode($filtered),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Data migration: cannot restore removed URLs without a backup.
    }
};
