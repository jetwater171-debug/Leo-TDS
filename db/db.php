<?php

require_once __DIR__ . "/../cookies.php";
require_once __DIR__ . "/../logging.php";
require_once __DIR__ . "/../settings.php";
require_once __DIR__ . "/../paths.php";

class Db
{
    private $dbPath;

    public function __construct()
    {
        global $cloSettings;
        $this->dbPath = __DIR__ . '/' . $cloSettings['dbConnection'];
        if (!file_exists($this->dbPath)) {
            $created = $this->create_new_db();
            if (!$created)
                die("Couldn't create the SQLite database! Read logs for additional info.");
        }
    }

    public function get_trafficback_clicks($startdate, $enddate): array
    {
        $query = "SELECT * FROM trafficback WHERE time BETWEEN :startDate AND :endDate ORDER BY time DESC";
        $clicks = $this->exec_read_query($query, [$startdate => SQLITE3_INTEGER, $enddate => SQLITE3_INTEGER]);
        foreach ($clicks as &$click) {
            if (empty($click['params']))
                continue;
            $click['params'] = json_decode($click['params'], true);
            if ($click['params'] === null && json_last_error() !== JSON_ERROR_NONE) {
                add_log("errors", "Failed to parse trafficback params JSON for row " . $click['id'] . ": " . json_last_error_msg());
                $click['params'] = [];
            }
        }
        return $clicks;
    }

    private function get_campaign_clicks(int $startdate, int $enddate, int $campId, bool $blocked = false): array
    {
        $query = "SELECT * FROM " . ($blocked ? "blocked" : "clicks") . " WHERE time BETWEEN :startDate AND :endDate AND campaign_id = :campid ORDER BY time DESC";
        $clicks = $this->exec_read_query($query, [$startdate => SQLITE3_INTEGER, $enddate => SQLITE3_INTEGER, $campId => SQLITE3_INTEGER]);
        foreach ($clicks as &$click) {
            if (empty($click['params']))
                continue;
            $click['params'] = json_decode($click['params'], true);
            if ($click['params'] === null && json_last_error() !== JSON_ERROR_NONE) {
                add_log("errors", "Failed to parse trafficback params JSON for row " . $click['id'] . ": " . json_last_error_msg());
                $click['params'] = [];
            }
        }
        return $clicks;
    }

    public function get_white_clicks(int $startdate, int $enddate, int $campId): array
    {
        return $this->get_campaign_clicks($startdate, $enddate, $campId, true);
    }
    public function get_black_clicks(int $startdate, int $enddate, int $campId): array
    {
        return $this->get_campaign_clicks($startdate, $enddate, $campId, false);
    }

    public function get_clicks_by_subid(string $subid, bool $firstOnly = false): array
    {
        if (empty($subid)) {
            add_log("trace", "Skipping clicks retrieval - empty subid provided");
            return [];
        }

        $query = "SELECT * FROM clicks WHERE subid = :subid ORDER BY time DESC";
        if ($firstOnly)
            $query .= " LIMIT 1";
        $clicks = $this->exec_read_query($query, [$subid => SQLITE3_TEXT]);
        foreach ($clicks as &$click) {
            if (empty($click['params']))
                continue;
            $click['params'] = json_decode($click['params'], true);
            if ($click['params'] === null && json_last_error() !== JSON_ERROR_NONE) {
                add_log("errors", "Failed to parse trafficback params JSON for row " . $click['id'] . ": " . json_last_error_msg());
                $click['params'] = [];
            }
        }
        return $firstOnly ? $clicks[0] ?? [] : $clicks;
    }

    public function get_leads($startdate, $enddate, $campId): array
    {
        // Prepare SQL query to select leads within the date range and configuration
        $query = "SELECT * FROM clicks WHERE time BETWEEN :startDate AND :endDate AND campaign_id = :campid AND status IS NOT NULL ORDER BY time DESC";

        $clicks = $this->exec_read_query($query, [$startdate => SQLITE3_INTEGER, $enddate => SQLITE3_INTEGER, $campId => SQLITE3_INTEGER]);
        foreach ($clicks as &$click) {
            if (empty($click['params']))
                continue;
            $click['params'] = json_decode($click['params'], true);
            if ($click['params'] === null && json_last_error() !== JSON_ERROR_NONE) {
                add_log("errors", "Failed to parse trafficback params JSON for row " . $click['id'] . ": " . json_last_error_msg());
                $click['params'] = [];
            }
        }
        return $clicks;
    }

    private function get_stats_select_parts(array $selectedFields): array
    {
        $selectParts = [];
        // Process selected fields
        foreach ($selectedFields as $field) {
            switch ($field) {
                case 'clicks':
                    $selectParts[] = "COUNT(c.id) AS clicks";
                    break;
                case 'uniques':
                    $selectParts[] = "COUNT(DISTINCT subid) AS uniques";
                    break;
                case 'uniques_ratio':
                    $selectParts[] = "(COUNT(DISTINCT subid)*1.0/COUNT(*) * 100.0) AS uniques_ratio";
                    break;
                case 'lpclicks':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN lpclick = 1 THEN c.id END) AS lpclicks";
                    break;
                case 'lpctr':
                    $selectParts[] = "(COUNT(DISTINCT CASE WHEN lpclick = 1 THEN c.id END) * 100.0 / COUNT(*)) AS lpctr";
                    break;
                case 'cra':
                    $selectParts[] = "(COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN c.id END) * 100.0 / COUNT(*)) AS cra";
                    break;
                case 'crs':
                    $selectParts[] = "(COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN c.id END) * 100.0 / COUNT(*)) AS crs";
                    break;
                case 'epc':
                    $selectParts[] = "(SUM(payout) * 1.0 / COUNT(c.id)) AS epc";
                    break;
                case 'uepc':
                    $selectParts[] = "(SUM(payout) * 1.0 / COUNT(DISTINCT(subid))) AS uepc";
                    break;
                case 'cpc':
                    $selectParts[] = "(SUM(cost) * 1.0 / COUNT(c.id)) AS cpc";
                    break;
                case 'ucpc':
                    $selectParts[] = "(SUM(cost) * 1.0 / COUNT(DISTINCT(subid))) AS ucpc";
                    break;
                case 'appt':
                    $selectParts[] = "CASE
                            WHEN COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN c.id END) = 0
                                 OR (COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN c.id END) - COUNT(DISTINCT CASE WHEN status = 'Trash' THEN c.id END)) = 0
                            THEN 0
                            ELSE (COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN c.id END) * 100.0 / (COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN c.id END) - COUNT(DISTINCT CASE WHEN status = 'Trash' THEN c.id END)))
                       END AS appt";
                    break;
                case 'app':
                    $selectParts[] = "CASE
                            WHEN COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN c.id END) = 0
                                 OR COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN c.id END) = 0
                            THEN 0
                            ELSE (COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN c.id END) * 100.0 / COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN c.id END))
                       END AS app";
                    break;
                case 'conversion':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN subid END) AS conversion";
                    break;
                case 'purchase':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN subid END) AS purchase";
                    break;
                case 'hold':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status = 'Lead' THEN subid END) AS hold";
                    break;
                case 'reject':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status = 'Reject' THEN subid END) AS reject";
                    break;
                case 'trash':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status = 'Trash' THEN subid END) AS trash";
                    break;
                case 'ec':
                    $selectParts[] = "(SUM(payout) * 1.0 / COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN subid END)) AS ec";
                    break;
                case 'cpa':
                    $selectParts[] = "(SUM(cost) * 1.0 / COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN subid END)) AS cpa";
                    break;
                case 'revenue':
                    $selectParts[] = "SUM(payout) AS revenue";
                    break;
                case 'costs':
                    $selectParts[] = "SUM(cost) AS costs";
                    break;
                case 'profit':
                    $selectParts[] = "(SUM(payout) - SUM(cost)) as profit";
                    break;
                case 'roi':
                    $selectParts[] = "((SUM(payout) - SUM(cost))*1.0 / SUM(cost) * 100.0) as roi";
                    break;
            }
        }
        return $selectParts;
    }

    public function get_statistics(
        array $selectedFields,
        array $groupByFields,
        int $campId,
        string $startDate,
        string $endDate,
        string $timezone
    ): array {
        $baseQuery =
            "SELECT %s FROM clicks c WHERE campaign_id = :campid AND time BETWEEN :startDate AND :endDate";
        $selectParts = [];
        $groupByParts = [];
        $orderByParts = [];

        $selectParts = $this->get_stats_select_parts($selectedFields);

        // Process group by fields
        foreach ($groupByFields as $field) {
            if ($field === 'date') {

                $dateTime = new DateTime('now', new DateTimeZone($timezone));
                // Get the offset in seconds from UTC
                $offsetInSeconds = $dateTime->getOffset();
                // Convert this offset to an SQLite compatible format (HH:MM)
                $hours = floor($offsetInSeconds / 3600);
                $minutes = floor(($offsetInSeconds % 3600) / 60);
                $offsetFormatted = sprintf('%+03d:%02d', $hours, $minutes);

                $selectParts[] =
                    "strftime('%Y-%m-%d', datetime(time, 'unixepoch', '{$offsetFormatted}')) AS date";
                $groupByParts[] = "date";
                $orderByParts[] = "date";
            } elseif (in_array($field, ['country', 'lang', 'os', 'osver', 'brand', 'model', 'device', 'isp', 'client', 'clientver', 'preland', 'land', 'flow'])) {
                $selectParts[] = $field;
                $groupByParts[] = $field;
                $orderByParts[] = $field;
            } else {
                // JSON fields
                $jsonExtract = "COALESCE(json_extract(params, '$." . $field . "'), 'unknown') AS " . $field;
                $selectParts[] = $jsonExtract;
                $groupByParts[] = $field;
                $orderByParts[] = $field;
            }
        }

        // Construct the SQL query
        $selectClause = implode(', ', $selectParts);
        $groupByClause = !empty($groupByParts) ? "GROUP BY " . implode(', ', $groupByParts) : '';
        $orderByClause = !empty($orderByParts) ? "ORDER BY " . implode(', ', $orderByParts) : '';
        $sqlQuery = sprintf($baseQuery, $selectClause) . " " . $groupByClause . " " . $orderByClause;

        $db = $this->open_db(true);
        $stmt = $db->prepare($sqlQuery);
        if ($stmt === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Error preparing statistics statement: $errorMessage");
            $db->close();
            return [];
        }

        $stmt->bindValue(':campid', $campId, SQLITE3_INTEGER);
        $stmt->bindValue(':startDate', $startDate, SQLITE3_INTEGER);
        $stmt->bindValue(':endDate', $endDate, SQLITE3_INTEGER);
        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Error executing statistics statement: $errorMessage");
            $db->close();
            return [];
        }

        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        $db->close();

        // Build the tree structure
        $tree = $this->build_tree($rows, $groupByFields, $selectedFields);
        return $tree;
    }

    private function build_tree(array $rows, array $groupByFields, array $selectedFields, int $level = 0): array
    {
        if (empty($groupByFields) || $level >= count($groupByFields)) {
            return $rows;
        }

        $groupField = $groupByFields[$level];
        $groupedData = [];

        // Group rows by current level's field
        foreach ($rows as $row) {
            $groupValue = $row[$groupField];
            // Convert all numeric values to strings to prevent implicit conversions
            if (is_numeric($groupValue)) {
                $groupValue = (string) $groupValue;
            }
            if (!isset($groupedData[$groupValue])) {
                $groupedData[$groupValue] = [];
            }
            $groupedData[$groupValue][] = $row;
        }

        $tree = [];
        foreach ($groupedData as $groupValue => $groupRows) {
            // For leaf nodes or single level grouping
            if ($level >= count($groupByFields) - 1) {
                $totals = $this->calculate_totals($groupRows, $selectedFields);
                $totals['group'] = $groupValue;
                $tree[] = $totals;
            } else {
                $children = $this->build_tree($groupRows, $groupByFields, $selectedFields, $level + 1);
                $totals = $this->calculate_totals($groupRows, $selectedFields);
                $node = array_merge(
                    array_diff_key($totals, array_flip($groupByFields)),
                    ['_children' => $children],
                    ['group' => $groupValue]  // Put this last to override any 'group' from totals
                );
                $tree[] = $node;
            }
        }

        return $tree;
    }

    private function calculate_totals(array $rows, array $selectedFields): array
    {
        $totals = array_fill_keys($selectedFields, 0);

        foreach ($rows as $row) {
            foreach ($selectedFields as $field) {
                if (isset($row[$field]) && is_numeric($row[$field])) {
                    $totals[$field] += $row[$field];
                }
            }
        }

        // Calculate derived fields
        if (in_array('uniques_ratio', $selectedFields))
            $totals['uniques_ratio'] = $totals['clicks'] === 0 ? 0 : $totals['uniques'] * 1.0 / $totals['clicks'] * 100;
        if (in_array('lpctr', $selectedFields))
            $totals['lpctr'] = $totals['clicks'] === 0 ? 0 : $totals['lpclicks'] * 1.0 / $totals['clicks'] * 100.0;
        if (in_array('cra', $selectedFields))
            $totals['cra'] = $totals['clicks'] === 0 ? 0 : $totals['conversion'] * 1.0 / $totals['clicks'] * 100.0;
        if (in_array('crs', $selectedFields))
            $totals['crs'] = $totals['clicks'] === 0 ? 0 : $totals['purchase'] * 1.0 / $totals['clicks'] * 100.0;
        if (in_array('appt', $selectedFields))
            $totals['appt'] = $totals['conversion'] - $totals['trash'] === 0 ? 0 : $totals['purchase'] * 1.0 / ($totals['conversion'] - $totals['trash']) * 100.0;
        if (in_array('app', $selectedFields))
            $totals['app'] = $totals['conversion'] === 0 ? 0 : $totals['purchase'] * 1.0 / $totals['conversion'] * 100.0;
        if (in_array('cpc', $selectedFields))
            $totals['cpc'] = $totals['clicks'] === 0 ? 0 : $totals['costs'] * 1.0 / $totals['clicks'];
        if (in_array('epc', $selectedFields))
            $totals['epc'] = $totals['clicks'] === 0 ? 0 : $totals['revenue'] * 1.0 / $totals['clicks'];
        if (in_array('epuc', $selectedFields))
            $totals['epuc'] = $totals['uniques'] === 0 ? 0 : $totals['revenue'] * 1.0 / $totals['uniques'] * 100;

        return $totals;
    }

    private function add_click(string $query, array $click): bool
    {
        $db = null;
        try {
            $db = $this->open_db();
            $stmt = $db->prepare($query);

            if ($stmt === false) {
                throw new Exception($db->lastErrorMsg());
            }

            foreach ($click as $key => $value) {
                if (!isset($value)) {
                    add_log("warning", "Null value found for field '$key' in click data");
                    $value = '';
                }
                $stmt->bindValue(':' . $key, $value);
            }

            $result = $stmt->execute();
            if ($result === false) {
                throw new Exception($db->lastErrorMsg());
            }

            add_log("trace", "Successfully added click for IP: " . ($click['ip'] ?? 'unknown'));
            return true;
        } catch (Exception $e) {
            add_log("errors", "Failed to add click: " . $e->getMessage() . ", Data: " . json_encode($click));
            return false;
        } finally {
            if (isset($db))
                $db->close();
        }
    }

    public function add_trafficback_click($data): bool
    {
        $click = $this->prepare_click_data($data);
        $query = "INSERT INTO trafficback (time, ip, country, lang, os, osver, brand, model, isp, client, clientver, ua, params) VALUES (:time, :ip, :country, :lang, :os, :osver, :brand, :model, :isp, :client, :clientver, :ua, :params)";
        return $this->add_click($query, $click);
    }

    public function add_white_click($data, $reason, $campId): bool
    {
        $click = $this->prepare_click_data($data, $campId);
        $click['reason'] = $reason;
        $query = "INSERT INTO blocked (campaign_id, time, ip, country, lang, os, osver, brand, model, isp, client, clientver, ua, reason, params) VALUES (:campaign_id, :time, :ip, :country, :lang, :os, :osver, :brand, :model, :isp, :client, :clientver, :ua, :reason, :params)";
        return $this->add_click($query, $click);
    }

    public function add_black_click($subid, $data, $preland, $land, $flow, $campId): bool
    {
        $click = $this->prepare_click_data($data, $campId);
        $click['subid'] = $subid;
        $click['preland'] = empty($preland) ? 'unknown' : $preland;
        $click['land'] = empty($land) ? 'unknown' : $land;
        $click['flow'] = empty($flow) ? 'unknown' : $flow;
        $click['lpclick'] = 0;
        $click['status'] = null;

        $query = "INSERT INTO clicks (campaign_id, time, ip, country, lang, os, osver, client, clientver, device, brand, model, isp, ua, subid, preland, land, flow, params, cost, lpclick, status) VALUES (:campaign_id, :time, :ip, :country, :lang, :os, :osver, :client, :clientver, :device, :brand, :model, :isp, :ua, :subid, :preland, :land, :flow, :params, :cpc, 0, NULL)";

        return $this->add_click($query, $click);
    }

    public function add_lead(string $subid, array $leaddata, string $status = 'Lead'): bool
    {
        if (empty($subid)) {
            add_log("warning", "Skipping lead addition - empty subid provided");
            return false;
        }

        $updateQuery = "UPDATE clicks SET status = :status, leaddata = :leaddata WHERE id = (SELECT id FROM clicks WHERE subid = :subid ORDER BY time DESC LIMIT 1)";
        return $this->exec_update_query($updateQuery, [$status => SQLITE3_TEXT, $leaddata => SQLITE3_TEXT, $subid => SQLITE3_TEXT]);
    }

    public function update_status(string $subid, string $status, float $payout): bool
    {
        if (empty($subid)) {
            add_log("warning", "Skipping status update - empty subid provided");
            return false;
        }

        if (!$this->subid_exists($subid)) {
            add_log("warning", "Skipping status update - subid not found: $subid");
            return false;
        }

        if (!is_numeric($payout)) {
            throw new Exception("Invalid payout value: $payout");
        }

        $updateQuery = "UPDATE clicks SET status = :status, payout = :payout WHERE id = (SELECT id FROM clicks WHERE subid = :subid ORDER BY time DESC LIMIT 1)";
        return $this->exec_update_query($updateQuery, [$subid => SQLITE3_TEXT, $status => SQLITE3_TEXT, $payout => SQLITE3_FLOAT]);
    }

    public function add_lpctr($subid): bool
    {
        if (empty($subid)) {
            add_log("warning", "Skipping lpctr update - empty subid provided");
            return false;
        }

        if (!$this->subid_exists($subid)) {
            add_log("warning", "Skipping lpctr update - subid not found: $subid");
            return false;
        }

        $updateQuery = "UPDATE clicks SET lpclick = 1 WHERE id = (SELECT id FROM clicks WHERE subid = :subid ORDER BY time DESC LIMIT 1)";
        return $this->exec_update_query($updateQuery, [$subid => SQLITE3_TEXT]);
    }

    public function update_click_params(int $clickId, array $params): bool
    {
        if (empty($clickId)) {
            add_log("warning", "Skipping params update - empty click ID provided");
            return false;
        }

        $paramsJson = json_encode($params);
        if ($paramsJson === false) {
            add_log("warning", "Failed to encode params to JSON for click ID: $clickId");
            return false;
        }

        $updateQuery = "UPDATE clicks SET params = :params WHERE id = :id";
        return $this->exec_update_query($updateQuery, [$paramsJson => SQLITE3_TEXT, $clickId => SQLITE3_INTEGER]);
    }

    private function subid_exists($subid): bool
    {
        if (empty($subid)) {
            add_log("warning", "Empty subid provided for existence check");
            return false;
        }
        $query = "SELECT COUNT(*) AS count FROM clicks WHERE subid = :subid";
        $res = $this->exec_read_query($query, [$subid => SQLITE3_TEXT]);
        return $res['count'] > 0;
    }

    private function prepare_click_data($data, $campId = null): array
    {
        $data["time"] = (new DateTime())->getTimestamp();
        if (!is_null($campId))
            $data["campaign_id"] = $campId;

        $query = [];
        if (!empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $query);
        }

        if (array_key_exists("cpc", $query)) {
            $data["cpc"] = $query["cpc"];
            unset($query["cpc"]);
        }

        $data["params"] = json_encode($query);
        return $data;
    }

    public function add_campaign($name): bool|int
    {
        $query = "INSERT INTO campaigns (name, settings) VALUES (:name, :settings)";

        $settingsJson = file_get_contents(__DIR__ . '/default.json');
        $settings = json_decode($settingsJson, true);
        $settings['apikey'] = $this->generate_api_key();
        $settingsJson = json_encode($settings);
        return $this->exec_write_query($query, [$name => SQLITE3_TEXT, $settingsJson => SQLITE3_TEXT], true);
    }

    private function generate_api_key(): string
    {
        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(16384, 20479),
            mt_rand(32768, 49151),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535)
        );
    }

    public function get_campaign_by_apikey(string $apikey): array
    {
        $query = "SELECT * FROM campaigns WHERE settings->>'apikey' = :apikey";
        $camp = $this->exec_read_query($query, [$apikey => SQLITE3_TEXT], true);
        if (isset($camp['settings'])) {
            $camp['settings'] = json_decode($camp['settings'], true);
        }
        return $camp;
    }

    public function clone_campaign($id): bool|int
    {
        $query = "INSERT INTO campaigns (name, settings)
                  SELECT name || ' (Clone)', settings FROM campaigns WHERE id = :id";
        return $this->exec_write_query($query, [$id => SQLITE3_INTEGER], true);
    }

    public function get_campaign_settings(int $id): array
    {
        $query = "SELECT settings FROM campaigns WHERE id = :id";
        $arr = $this->exec_read_query($query, [$id => SQLITE3_INTEGER], true);
        $settings = json_decode($arr['settings'], true);
        return $settings;
    }

    public function get_campaign_by_domain(): array|bool
    {
        $cPath = get_cloaker_path(true, false);
        $parsedUrl = parse_url($cPath);
        $domain = isset($parsedUrl['port']) ?
            $parsedUrl['host'] . ":" . $parsedUrl['port'] :
            $parsedUrl['host'];

        $query = "SELECT * FROM campaigns";
        $campaigns = $this->exec_read_query($query, []);
        foreach ($campaigns as $campaign) {
            if (empty($campaign['settings'])) {
                continue;
            }
            $settings = json_decode($campaign['settings'], true);
            if (!isset($settings['domains'])) {
                continue;
            }
            if ($this->match_domain($settings['domains'], $domain)) {
                add_log("trace", "Found matching campaign for domain $domain: " . $campaign['id']);
                $campaign['settings'] = $settings;
                return $campaign;
            }
        }
        return false;
    }

    private function match_domain($domains, $domainToMatch): bool
    {
        foreach ($domains as $domain) {
            if ($domain === $domainToMatch) {
                return true;
            } elseif (strpos($domain, '*') !== false) {
                // Convert wildcard domain to a regex pattern
                $pattern = str_replace('.', '\.', $domain);
                $pattern = str_replace('*', '.*', $pattern);
                if (preg_match('/^' . $pattern . '$/', $domainToMatch)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function rename_campaign(int $id, string $name): bool
    {
        $query = "UPDATE campaigns SET name = :name WHERE id = :id";
        return $this->exec_write_query($query, [$name => SQLITE3_TEXT, $id => SQLITE3_INTEGER]);
    }

    public function save_campaign_settings(int $id, array $settings): bool
    {
        $query = "UPDATE campaigns SET settings = :settings WHERE id = :id";
        $settingsJson = json_encode($settings);
        return $this->exec_write_query($query, [$settingsJson => SQLITE3_TEXT, $id => SQLITE3_INTEGER]);
    }


    public function delete_campaign(int $id): bool
    {
        $query = "DELETE FROM campaigns WHERE id = :id";
        return $this->exec_write_query($query, [$id => SQLITE3_INTEGER]);
    }

    public function get_campaigns($startDate, $endDate, array $selectFields): array
    {
        $query = "
        SELECT cmp.id, cmp.name, %s
        FROM campaigns cmp
        LEFT JOIN clicks c ON c.campaign_id=cmp.id AND c.time BETWEEN :startDate AND :endDate
        GROUP BY cmp.id";

        $selectClause = implode(',', $this->get_stats_select_parts($selectFields));
        $query = sprintf($query, $selectClause);

        $campaigns = $this->exec_read_query($query, [$startDate => SQLITE3_INTEGER, $endDate => SQLITE3_INTEGER]);
        foreach ($campaigns as &$campaign) {
            if (empty($campaign['settings']))
                continue;
            $campaign['settings'] = json_decode($campaign['settings'], true);
        }
        return $campaigns;
    }

    public function get_common_settings(): array
    {
        $query = "SELECT settings FROM common";

        $arr = $this->exec_read_query($query, [], true);
        $settings = json_decode($arr['settings'], true);
        if ($settings === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse common settings JSON: " . json_last_error_msg());
        }
        return $settings;
    }

    public function set_common_settings(array $s): bool
    {
        $query = "UPDATE common SET settings=:settings";
        $settingsJson = json_encode($s);
        if ($settingsJson === false) {
            throw new Exception("Failed to encode settings to JSON: " . json_last_error_msg());
        }
        return $this->exec_write_query($query, [$settingsJson => SQLITE3_TEXT]);
    }

    private function open_db(bool $readOnly = false): SQLite3
    {
        $db = new SQLite3($this->dbPath, $readOnly ? SQLITE3_OPEN_READONLY : SQLITE3_OPEN_READWRITE);
        $db->busyTimeout(5000);

        // Optimizations
        $db->exec('PRAGMA mmap_size = 268435456');    // 256MB memory mapping
        $db->exec('PRAGMA cache_size = -64000');      // 64MB cache pages  
        $db->exec('PRAGMA temp_store = MEMORY');      // temporary data in RAM

        if (!$readOnly) {
            $db->exec('PRAGMA synchronous = OFF');    // only for writing
        }

        return $db;
    }

    private function exec_write_query(string $query, array $p, bool $returnId = false): bool|int
    {
        $db = null;
        try {
            $db = $this->open_db();
            $db->exec('BEGIN IMMEDIATE');
            $stmt = $db->prepare($query);

            if ($stmt === false) {
                throw new Exception("Error preparing $query: " . $db->lastErrorMsg());
            }

            $keys = array_keys($p);
            foreach ($keys as $index => $key) {
                $bound = $stmt->bindValue($index + 1, $key, $p[$key]);
                if ($bound === false) {
                    throw new Exception("Error binding $key to $query: " . $db->lastErrorMsg());
                }
            }

            $result = $stmt->execute();

            if ($result === false) {
                throw new Exception("Error executing $query: " . $db->lastErrorMsg());
            }

            $db->exec('COMMIT');
            add_log("trace", "Successfully executed $query");
            return $returnId ? $db->lastInsertRowID() : true;
        } catch (Exception $e) {
            if (isset($db))
                $db->exec('ROLLBACK');
            add_log("errors", $e->getMessage());
            return false;
        } finally {
            if (isset($db))
                $db->close();
        }
    }

    private function exec_update_query(string $query, array $p): bool
    {
        $db = null;
        try {
            $db = $this->open_db();
            $stmt = $db->prepare($query);
            if ($stmt === false) {
                throw new Exception("Failed to prepare $query: " . $db->lastErrorMsg());
            }

            $keys = array_keys($p);
            foreach ($keys as $index => $key) {
                $bound = $stmt->bindValue($index + 1, $key, $p[$key]);
                if ($bound === false) {
                    throw new Exception("Failed to bind $key to $query: " . $db->lastErrorMsg());
                }
            }

            $result = $stmt->execute();
            if ($result === false) {
                throw new Exception("Failed to execute $query: " . $db->lastErrorMsg());
            }

            if ($db->changes() === 0) {
                add_log("errors", "No rows affected when $query");
                return false;
            }
            return true;
        } catch (Exception $e) {
            add_log("errors", $e->getMessage());
            return false;
        } finally {
            if (isset($db))
                $db->close();
        }
    }

    private function exec_read_query(string $query, array $p, bool $firstOnly = false): array
    {
        $db = null;
        try {
            $db = $this->open_db(true);
            $stmt = $db->prepare($query);
            if ($stmt === false) {
                throw new Exception("Error preparing $query: " . $db->lastErrorMsg());
            }

            $keys = array_keys($p);
            foreach ($keys as $index => $key) {
                $bound = $stmt->bindValue($index + 1, $key, $p[$key]);
                if ($bound === false) {
                    throw new Exception("Error binding $key to $query: " . $db->lastErrorMsg());
                }
            }

            $result = $stmt->execute();
            if ($result === false) {
                throw new Exception("Error executing $query: " . $db->lastErrorMsg());
            }

            $arr = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $arr[] = $row;
            }
            return $firstOnly ? $arr[0] ?? [] : $arr;
        } catch (Exception $e) {
            add_error_log($e->getMessage());
            return [];
        } finally {
            if (isset($db)) {
                $db->close();
            }
        }
    }

    private function create_new_db(): bool
    {
        $db = null;
        try {
            // Read SQL schema and initial settings
            $createTableSQL = @file_get_contents(__DIR__ . "/db.sql");
            if ($createTableSQL === false) {
                throw new Exception("Failed to read database schema file");
            }

            $settingsJson = @file_get_contents(__DIR__ . '/common.json');
            if ($settingsJson === false) {
                throw new Exception("Failed to read common settings file");
            }

            // Initialize database
            $db = new SQLite3($this->dbPath, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
            $db->busyTimeout(5000);


            // Create tables
            $result = $db->exec($createTableSQL);
            if ($result === false) {
                throw new Exception($db->lastErrorMsg());
            }

            // Insert initial settings
            $query = "INSERT INTO common (settings) VALUES (:settings)";
            $stmt = $db->prepare($query);

            if ($stmt === false) {
                throw new Exception($db->lastErrorMsg());
            }

            $stmt->bindValue(':settings', $settingsJson, SQLITE3_TEXT);
            $result = $stmt->execute();

            if ($result === false) {
                throw new Exception($db->lastErrorMsg());
            }

            add_log("trace", "Successfully initialized database with schema and common settings");
            return true;
        } catch (Exception $e) {
            if (isset($db)) {
                $db->exec('ROLLBACK');
                add_log("errors", "Failed to initialize database: " . $e->getMessage());
            } else {
                die("Critical error initializing database: " . $e->getMessage());
            }
            return false;
        } finally {
            if (isset($db))
                $db->close();
        }
    }
}

$db = new Db();