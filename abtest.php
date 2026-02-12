<?php
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/logging.php';
require_once __DIR__ . '/campaign.php';

class AbTest
{
    private Campaign $campaign;
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function select_folder(array $folders, string $folder_type): array
    {
        return $this->select_item($folders, $folder_type, true);
    }

    public function select_item(array $items, string $item_type, bool $is_folder): array
    {
        if (empty($items))
            add_error_log("No items found for $item_type in campaign {$this->campaign->campaignId}!", false, true);

        $item = '';

        if ($this->campaign->saveUserFlow) {
            $item = $this->get_saved_item($item_type, $items, $is_folder);
        }

        if ($item === '') {
            $item = $this->get_random_item($items);
        }

        set_cookie($item_type, $item);
        return [$item, array_search($item, $items)];
    }

    private function get_saved_item(string $item_type, array $items, bool $is_folder): string
    {
        $item = get_cookie($item_type);

        if (!in_array($item, $items, true) || ($is_folder && !$this->is_folder_valid($item))) {
            return '';
        }

        return $item;
    }

    private function is_folder_valid(string $item): bool
    {
        return is_dir(__DIR__ . '/' . $item);
    }

    private function get_random_item(array $items): string
    {
        $random_index = rand(0, count($items) - 1);
        $item = $items[$random_index];
        return $item;
    }

    private function get_weighted_item(array $items, array $weights): string
    {
        $total = array_sum($weights);
        if ($total <= 0) {
            return $this->get_random_item($items);
        }
        $rand = mt_rand(1, (int)$total);
        $cumulative = 0;
        for ($i = 0; $i < count($items); $i++) {
            $cumulative += $weights[$i] ?? 0;
            if ($rand <= $cumulative) {
                return $items[$i];
            }
        }
        return $items[count($items) - 1];
    }

    public function select_distributed(array $items, string $item_type, bool $is_folder, string $distribution, array $weights): array
    {
        if (empty($items))
            add_error_log("No items found for $item_type in campaign {$this->campaign->campaignId}!", false, true);

        $item = '';

        if ($this->campaign->saveUserFlow) {
            $item = $this->get_saved_item($item_type, $items, $is_folder);
        }

        if ($item === '') {
            $item = ($distribution === 'weighted' && !empty($weights))
                ? $this->get_weighted_item($items, $weights)
                : $this->get_random_item($items);
        }

        set_cookie($item_type, $item);
        return [$item, array_search($item, $items)];
    }

}