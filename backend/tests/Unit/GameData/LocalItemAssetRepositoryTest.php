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
        $icon = $root.'/texture/item/ketupat.bmp';
        $illustration = $root.'/texture/collection/ketupat.bmp';
        $map = $root.'/item-assets.json';
        $filesystem->ensureDirectoryExists(dirname($icon));
        $filesystem->ensureDirectoryExists(dirname($illustration));
        $filesystem->put($icon, 'icon');
        $filesystem->put($illustration, 'illustration');
        $filesystem->put($map, json_encode(['items' => [
            '552' => [
                'icon' => 'texture/item/ketupat.bmp',
                'illustration' => 'texture/collection/ketupat.bmp',
            ],
        ]], JSON_THROW_ON_ERROR));
        $assets = new LocalItemAssetRepository($map, $root);

        try {
            $this->assertSame(realpath($icon), $assets->iconPath(552));
            $this->assertSame(realpath($illustration), $assets->illustrationPath(552));
            $this->assertNull($assets->iconPath(999));
        } finally {
            $filesystem->deleteDirectory($root);
        }
    }
}
