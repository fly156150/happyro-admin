<?php

namespace App\Services\GameData;

use App\Contracts\GameData\GameDataSettingRepository;
use App\Contracts\GameData\ItemRepository;
use App\Data\GameData\ItemQuery;
use App\Models\GameDataCatalog;
use App\Models\GameItemView;
use Illuminate\Database\Eloquent\Builder;

final class DatabaseItemRepository implements ItemRepository
{
    public function __construct(private readonly GameDataSettingRepository $settings) {}

    public function search(ItemQuery $query): array
    {
        $builder = $this->query($query->range)
            ->when($query->query, fn (Builder $items, string $value): Builder => $this->matching($items, $value))
            ->when($query->type, fn (Builder $items, string $value): Builder => $items->where('item_type', $value))
            ->when($query->subtype, fn (Builder $items, string $value): Builder => $items->where('item_subtype', $value));
        $total = (clone $builder)->count();
        $records = $builder
            ->with($this->relations())
            ->orderBy('item_id')
            ->forPage($query->page, $query->perPage)
            ->get();

        return ['data' => $records->map($this->payload(...))->all(), 'total' => $total];
    }

    public function find(int $itemId, string $range): ?array
    {
        $record = $this->query($range)
            ->with($this->relations())
            ->where('item_id', $itemId)
            ->first();

        return $record ? $this->payload($record) : null;
    }

    private function query(string $range): Builder
    {
        $versions = $this->settings->current();
        $clientCatalog = $this->catalogId('client', 'client', $versions->client);
        $serverCatalog = $this->catalogId('server', 'renewal', $versions->server);

        return GameItemView::query()
            ->where('client_catalog_id', $clientCatalog ?? 0)
            ->where('server_catalog_id', $serverCatalog ?? 0)
            ->when($range === 'client', fn (Builder $query): Builder => $query->where('client_exists', true))
            ->when($range === 'server', fn (Builder $query): Builder => $query->where('server_exists', true));
    }

    private function catalogId(string $source, string $ruleset, string $version): ?int
    {
        return GameDataCatalog::query()
            ->where('resource_type', 'items')
            ->where('source', $source)
            ->where('ruleset', $ruleset)
            ->where('source_version', $version)
            ->value('id');
    }

    private function matching(Builder $builder, string $value): Builder
    {
        $escaped = addcslashes($value, '%_\\');

        return $builder->where(function (Builder $match) use ($escaped, $value): void {
            $match->where('name_zh_cn', 'like', "%{$escaped}%")
                ->orWhere('name_en_us', 'like', "%{$escaped}%")
                ->orWhere('aegis_name', 'like', "%{$escaped}%");
            if (ctype_digit($value)) {
                $match->orWhere('item_id', (int) $value);
            }
        });
    }

    /** @return list<string> */
    private function relations(): array
    {
        return [
            'clientCatalog:id,source_version',
            'serverCatalog:id,source_version',
        ];
    }

    /** @return array<string, mixed> */
    private function payload(GameItemView $view): array
    {
        return [
            'Id' => $view->item_id,
            ...$view->payload,
            'AegisName' => $view->aegis_name,
            'Type' => $view->item_type,
            'SubType' => $view->item_subtype,
            'Buy' => $view->buy,
            'Sell' => $view->sell,
            'Weight' => $view->weight,
            'Attack' => $view->attack,
            'Defense' => $view->defense,
            'Slots' => $view->slots,
            'Script' => $view->script,
            'names' => ['zh-CN' => $view->name_zh_cn, 'en-US' => $view->name_en_us],
            'resourceName' => $view->resource_name,
            'description' => $view->description,
            'source' => $this->source($view),
            'fieldSources' => $view->field_sources,
            'clientResourceVersion' => $view->client_exists ? $view->clientCatalog->source_version : null,
            'serverVersion' => $view->server_exists ? $view->serverCatalog->source_version : null,
        ];
    }

    private function source(GameItemView $view): string
    {
        if ($view->client_exists && $view->server_exists) {
            return 'both';
        }

        return $view->client_exists ? 'client' : 'server';
    }
}
