<?php
/**
 * SneakX — Classe Model de base
 * /backend/classes/Model.php
 */

abstract class Model {
    protected PDO    $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ─── CRUD DE BASE ───────────────────────────

    public function find(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function all(string $orderBy = 'id DESC', int $limit = 0, int $offset = 0): array {
        $sql = "SELECT * FROM `{$this->table}` ORDER BY {$orderBy}";
        if ($limit)  $sql .= " LIMIT {$limit}";
        if ($offset) $sql .= " OFFSET {$offset}";
        return $this->db->query($sql)->fetchAll();
    }

    public function insert(array $data): int {
        $cols  = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $colsStr = implode('`, `', $cols);
        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}` (`{$colsStr}`) VALUES ({$placeholders})"
        );
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        if (empty($data)) return false;
        $sets = implode(' = ?, ', array_map(fn($k) => "`{$k}`", array_keys($data))) . ' = ?';
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET {$sets} WHERE `{$this->primaryKey}` = ?"
        );
        $values   = array_values($data);
        $values[] = $id;
        return $stmt->execute($values);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?"
        );
        return $stmt->execute([$id]);
    }

    public function count(string $where = '', array $params = []): int {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        if ($where) $sql .= " WHERE {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // ─── PAGINATION ─────────────────────────────

    public function paginate(
        int    $page    = 1,
        int    $perPage = 12,
        string $where   = '',
        array  $params  = [],
        string $orderBy = 'id DESC'
    ): array {
        $offset = ($page - 1) * $perPage;
        $sql    = "SELECT * FROM `{$this->table}`";
        if ($where) $sql .= " WHERE {$where}";
        $sql   .= " ORDER BY {$orderBy} LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([...$params, $perPage, $offset]);
        $items = $stmt->fetchAll();
        $total = $this->count($where, $params);

        return [
            'items'        => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    // ─── REQUÊTES PROTÉGÉES ─────────────────────

    protected function query(string $sql, array $params = []): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function queryOne(string $sql, array $params = []): ?array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    protected function queryValue(string $sql, array $params = []): mixed {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    protected function execute(string $sql, array $params = []): bool {
        return $this->db->prepare($sql)->execute($params);
    }
}
