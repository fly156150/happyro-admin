<?php

namespace Tests\Unit\GameData;

use App\Services\GameData\LocalItemAssetRepository;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

final class LocalItemAssetRepositoryTest extends TestCase
{
    public function test_resolves_manifest_backed_asset_paths(): void
    {
        $filesystem = new Filesystem;
        $root = sys_get_temp_dir().'/happyro-item-assets-'.bin2hex(random_bytes(8));
        $icon = $root.'/icons/552.png';
        $illustration = $root.'/illustrations/552.png';
        $filesystem->ensureDirectoryExists(dirname($icon));
        $filesystem->ensureDirectoryExists(dirname($illustration));
        $filesystem->put($icon, 'icon');
        $filesystem->put($illustration, 'illustration');
        $assets = new LocalItemAssetRepository($root);

        try {
            $this->assertSame(realpath($icon), $assets->iconPath(552));
            $this->assertSame(realpath($illustration), $assets->illustrationPath(552));
            $this->assertNull($assets->iconPath(999));
        } finally {
            $filesystem->deleteDirectory($root);
        }
    }
}
