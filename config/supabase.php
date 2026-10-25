<?php
/**
 * CampusKart - Supabase Integration Client for PHP
 *
 * Provides high-performance, secure REST and RPC connectivity to Supabase PostgreSQL.
 * Supports Row Level Security (RLS) policies via anonymous / authenticated keys
 * and trusted server-side transactions via service-role keys.
 */

declare(strict_types=1);

class SupabaseClient
{
    private string $url;
    private string $anonKey;
    private string $serviceRoleKey;
    private int $timeout;

    public function __construct(?string $url = null, ?string $anonKey = null, ?string $serviceRoleKey = null, int $timeout = 15)
    {
        $this->url = rtrim($url ?? (getenv('NEXT_PUBLIC_SUPABASE_URL') ?: (getenv('SUPABASE_URL') ?: '')), '/');
        $this->anonKey = $anonKey ?? (getenv('NEXT_PUBLIC_SUPABASE_ANON_KEY') ?: (getenv('SUPABASE_ANON_KEY') ?: ''));
        $this->serviceRoleKey = $serviceRoleKey ?? (getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '');
        $this->timeout = $timeout;
    }

    public function isConfigured(): bool
    {
        return !empty($this->url) && (!empty($this->serviceRoleKey) || !empty($this->anonKey));
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Executes an RPC call to a PostgreSQL function on Supabase.
     */
    public function rpc(string $functionName, array $params = [], bool $useServiceRole = true): mixed
    {
        $endpoint = "/rest/v1/rpc/{$functionName}";
        return $this->request($endpoint, 'POST', $params, $useServiceRole);
    }

    /**
     * Executes a raw SQL query on Supabase PostgreSQL via the secure server-side RPC.
     */
    public function query(string $sql, array $params = []): array
    {
        $formattedSql = $this->interpolateQuery($sql, $params);
        $result = $this->rpc('run_sql_query', ['query_text' => $formattedSql], true);
        return is_array($result) ? $result : [];
    }

    /**
     * Executes a raw SQL command (INSERT/UPDATE/DELETE) via secure server-side RPC.
     */
    public function execute(string $sql, array $params = []): int
    {
        $formattedSql = $this->interpolateQuery($sql, $params);
        $result = $this->rpc('run_sql_command', ['cmd_text' => $formattedSql], true);
        return is_numeric($result) ? (int)$result : 1;
    }

    /**
     * Low-level HTTP request method to Supabase REST API with cURL.
     */
    public function request(string $endpoint, string $method = 'GET', ?array $body = null, bool $useServiceRole = true): mixed
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException("Supabase is not configured. Missing URL or API keys.");
        }

        $key = ($useServiceRole && !empty($this->serviceRoleKey)) ? $this->serviceRoleKey : $this->anonKey;
        $url = $this->url . $endpoint;

        $headers = [
            "apikey: {$key}",
            "Authorization: Bearer {$key}",
            "User-Agent: CampusKart-Backend/1.0",
            "Content-Type: application/json",
            "Accept: application/json",
            "Prefer: return=representation"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        // Auto-detect CA bundle on Windows if not set in php.ini
        $caBundle = ini_get('curl.cainfo') ?: ini_get('openssl.cafile');
        $caFound = false;
        if (!empty($caBundle) && file_exists($caBundle)) {
            curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
            $caFound = true;
        } else {
            $possibleBundles = [
                'C:\Program Files\Git\mingw64\etc\ssl\certs\ca-bundle.crt',
                'C:\Program Files\Git\usr\ssl\certs\ca-bundle.crt',
                'C:\Windows\System32\curl-ca-bundle.crt',
            ];
            foreach ($possibleBundles as $bundle) {
                if (file_exists($bundle)) {
                    curl_setopt($ch, CURLOPT_CAINFO, $bundle);
                    $caFound = true;
                    break;
                }
            }
        }
        if (!$caFound && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body ?? new stdClass(), JSON_UNESCAPED_SLASHES));
        } elseif ($method === 'PATCH' || $method === 'PUT' || $method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
            }
        }

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            throw new RuntimeException("Supabase cURL request failed: " . $curlError);
        }

        $decoded = json_decode($rawResponse, true);

        if ($httpCode >= 400) {
            $msg = $decoded['message'] ?? ($decoded['hint'] ?? $rawResponse);
            throw new RuntimeException("Supabase API error [HTTP {$httpCode}]: " . $msg);
        }

        return $decoded;
    }

    /**
     * Safely escapes and binds parameters for SQL queries.
     */
    private function interpolateQuery(string $sql, array $params): string
    {
        if (empty($params)) {
            return $sql;
        }

        // Handle positional parameters (?)
        if (isset($params[0])) {
            $parts = explode('?', $sql);
            $interpolated = '';
            foreach ($parts as $i => $part) {
                $interpolated .= $part;
                if (isset($params[$i])) {
                    $interpolated .= $this->escapeSqlValue($params[$i]);
                }
            }
            return $interpolated;
        }

        // Handle named parameters (:name)
        uksort($params, fn($a, $b) => strlen($b) <=> strlen($a));
        foreach ($params as $key => $val) {
            $keyStr = ltrim($key, ':');
            $valStr = $this->escapeSqlValue($val);
            $sql = preg_replace('/:' . preg_quote($keyStr, '/') . '\b/', $valStr, $sql);
        }

        return $sql;
    }

    private function escapeSqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }
        return "'" . str_replace("'", "''", (string)$value) . "'";
    }
}

/**
 * PDO-compatible Statement adapter for Supabase.
 */
class SupabasePDOStatement extends PDOStatement
{
    private array $rows = [];
    private int $cursor = 0;
    private int $affectedRows = 0;
    private ?string $sql = null;
    private SupabaseClient $client;
    private array $boundParams = [];

    public function __construct(SupabaseClient $client, ?string $sql = null, array $initialRows = [])
    {
        $this->client = $client;
        $this->sql = $sql;
        $this->rows = $initialRows;
        $this->affectedRows = count($initialRows);
    }

    #[\ReturnTypeWillChange]
    public function execute(?array $params = null): bool
    {
        $allParams = array_merge($this->boundParams, $params ?? []);
        $cleanSql = trim($this->sql ?? '');

        // Determine if SELECT query or Mutation
        if (preg_match('/^\s*(SELECT|WITH|SHOW|EXPLAIN)\b/i', $cleanSql)) {
            $this->rows = $this->client->query($cleanSql, $allParams);
            $this->affectedRows = count($this->rows);
            $this->cursor = 0;
        } else {
            $this->affectedRows = $this->client->execute($cleanSql, $allParams);
            $this->rows = [];
            $this->cursor = 0;
        }

        return true;
    }

    #[\ReturnTypeWillChange]
    public function fetch(int $mode = PDO::FETCH_ASSOC, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        if ($this->cursor < count($this->rows)) {
            return $this->rows[$this->cursor++];
        }
        return false;
    }

    #[\ReturnTypeWillChange]
    public function fetchAll(int $mode = PDO::FETCH_ASSOC, mixed ...$args): array
    {
        if ($this->cursor > 0) {
            $slice = array_slice($this->rows, $this->cursor);
            $this->cursor = count($this->rows);
            return $slice;
        }
        $this->cursor = count($this->rows);
        return $this->rows;
    }

    #[\ReturnTypeWillChange]
    public function fetchColumn(int $columnNumber = 0): mixed
    {
        $row = $this->fetch();
        if ($row === false) {
            return false;
        }
        $values = array_values($row);
        return $values[$columnNumber] ?? false;
    }

    #[\ReturnTypeWillChange]
    public function rowCount(): int
    {
        return $this->affectedRows;
    }

    #[\ReturnTypeWillChange]
    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        $key = is_string($param) ? ltrim($param, ':') : $param;
        $this->boundParams[$key] = $value;
        return true;
    }
}

/**
 * PDO-compatible Connection adapter for Supabase.
 */
class SupabasePDO extends PDO
{
    private SupabaseClient $client;
    private bool $inTransaction = false;
    private ?string $lastInsertId = null;

    public function __construct(SupabaseClient $client)
    {
        parent::__construct('sqlite::memory:');
        $this->client = $client;
    }

    public function getClient(): SupabaseClient
    {
        return $this->client;
    }

    #[\ReturnTypeWillChange]
    public function query(string $statement, ?int $mode = PDO::FETCH_ASSOC, ...$fetchModeArgs): SupabasePDOStatement
    {
        $cleanSql = trim($statement);
        if (preg_match('/^\s*(SELECT|WITH|SHOW|EXPLAIN)\b/i', $cleanSql)) {
            $rows = $this->client->query($cleanSql);
            return new SupabasePDOStatement($this->client, $statement, $rows);
        }

        $affected = $this->client->execute($cleanSql);
        $stmt = new SupabasePDOStatement($this->client, $statement);
        return $stmt;
    }

    #[\ReturnTypeWillChange]
    public function prepare(string $query, array $options = []): SupabasePDOStatement
    {
        return new SupabasePDOStatement($this->client, $query);
    }

    #[\ReturnTypeWillChange]
    public function exec(string $statement): int
    {
        return $this->client->execute($statement);
    }

    #[\ReturnTypeWillChange]
    public function beginTransaction(): bool
    {
        $this->inTransaction = true;
        return true;
    }

    #[\ReturnTypeWillChange]
    public function commit(): bool
    {
        $this->inTransaction = false;
        return true;
    }

    #[\ReturnTypeWillChange]
    public function rollBack(): bool
    {
        $this->inTransaction = false;
        return true;
    }

    #[\ReturnTypeWillChange]
    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    #[\ReturnTypeWillChange]
    public function lastInsertId(?string $name = null): string|false
    {
        if ($this->lastInsertId !== null) {
            return $this->lastInsertId;
        }

        // If table sequence requested or generic lastval
        try {
            $seq = $name ? "currval('$name')" : "lastval()";
            $res = $this->client->query("SELECT $seq as id");
            if (!empty($res[0]['id'])) {
                return (string)$res[0]['id'];
            }
        } catch (Throwable $e) {
            // Ignore error and return false
        }

        return false;
    }

    public function setLastInsertId(string $id): void
    {
        $this->lastInsertId = $id;
    }
}
