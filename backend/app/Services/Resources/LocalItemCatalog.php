<?php

namespace App\Services\Resources;

use App\Contracts\Resources\ItemCatalog;
use RuntimeException;

final class LocalItemCatalog implements ItemCatalog
{
    private ?array $items = null;
    private ?array $iconMap = null;
    private ?array $descriptions = null;

    public function __construct(
        private readonly string $catalogPath,
        private readonly string $iconMapPath,
        private readonly string $descriptionPath,
        private readonly string $resourceRoot,
        private readonly string $iconRelativeRoot,
        private readonly string $illustrationRelativeRoot,
    ) {}

    public function search(?string $query, ?string $type, int $page, int $perPage): array
    {
        $items = array_values(array_filter($this->items(), function (array $item) use ($query, $type): bool {
            $names = array_values(array_filter($item['names'] ?? [], 'is_string'));
            $matchesQuery = !$query
                || str_contains(strtolower((string) ($item['AegisName'] ?? '')), strtolower($query))
                || (bool) array_filter($names, static fn (string $name): bool => str_contains($name, $query))
                || (string) ($item['Id'] ?? '') === $query;
            return $matchesQuery && (!$type || ($item['Type'] ?? '') === $type);
        }));
        $total = count($items);
        $offset = max(0, $page - 1) * $perPage;

        return ['data' => array_slice($items, $offset, $perPage), 'total' => $total];
    }

    public function find(int $id): ?array
    {
        foreach ($this->items() as $item) {
            if ((int) ($item['Id'] ?? 0) === $id) {
                return $item;
            }
        }

        return null;
    }

    private function items(): array
    {
        if ($this->items !== null) {
            return $this->items;
        }
        $payload = json_decode((string) file_get_contents($this->catalogPath), true, flags: JSON_THROW_ON_ERROR);
        $this->items = array_map(static fn (array $item, string $id): array => ['Id' => (int) $id, ...$item], $payload['items'], array_keys($payload['items']));

        return $this->items;
    }

    public function iconPath(int $id): ?string
    {
        return $this->resourcePath($id, $this->iconRelativeRoot);
    }

    public function illustrationPath(int $id): ?string
    {
        return $this->resourcePath($id, $this->illustrationRelativeRoot);
    }

    public function description(int $id): ?array
    {
        $lines = $this->descriptions()[(string) $id] ?? null;
        if (!is_array($lines)) return null;
        $cleaned = array_values(array_filter(array_map($this->cleanDescriptionLine(...), $lines), static fn (string $line): bool => $line !== ''));

        return $cleaned !== [] ? $cleaned : null;
    }

    private function cleanDescriptionLine(mixed $line): string
    {
        if (!is_string($line)) return '';
        $plain = preg_replace('/\^[0-9a-fA-F]{6}/', '', $line) ?? '';
        return trim($plain) === '_' ? '' : trim($plain);
    }

    private function resourcePath(int $id, string $relativeRoot): ?string
    {
        $resourceName = $this->iconMap()[(string) $id] ?? null;
        if (!$resourceName) return null;
        $relative = $relativeRoot . DIRECTORY_SEPARATOR . $resourceName . '.bmp';
        $path = realpath($this->resourceRoot . DIRECTORY_SEPARATOR . $relative);
        $root = realpath($this->resourceRoot);
        return $path && $root && str_starts_with($path, $root . DIRECTORY_SEPARATOR) && is_file($path) ? $path : null;
    }

    private function iconMap(): array
    {
        if ($this->iconMap !== null) return $this->iconMap;
        if (!is_file($this->iconMapPath)) throw new RuntimeException("Item icon map not found: {$this->iconMapPath}");
        $payload = json_decode((string) file_get_contents($this->iconMapPath), true, flags: JSON_THROW_ON_ERROR);
        return $this->iconMap = $payload['items'] ?? [];
    }

    private function descriptions(): array
    {
        if ($this->descriptions !== null) return $this->descriptions;
        if (!is_file($this->descriptionPath)) throw new RuntimeException("Item descriptions not found: {$this->descriptionPath}");
        $payload = json_decode((string) file_get_contents($this->descriptionPath), true, flags: JSON_THROW_ON_ERROR);
        return $this->descriptions = $payload['items'] ?? [];
    }
}
