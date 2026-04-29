<?php

function bvassist_assert_table_name(string $table): string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        throw new InvalidArgumentException('Invalid table name');
    }

    return $table;
}

function bvassist_fetch_rows(mysqli $conn, string $sql): array
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

function bvassist_fetch_stmt_rows(mysqli_stmt $stmt): array
{
    $rows = [];
    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        return $rows;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

function bvassist_has_column(mysqli $conn, string $table, string $column): bool
{
    $table = bvassist_assert_table_name($table);
    $column = bvassist_assert_table_name($column);
    $sql = "SHOW COLUMNS FROM `{$table}` LIKE '" . mysqli_real_escape_string($conn, $column) . "'";
    $result = mysqli_query($conn, $sql);

    return $result && mysqli_num_rows($result) > 0;
}

function getCount(mysqli $conn, string $table): int
{
    $table = bvassist_assert_table_name($table);
    $sql = "SELECT COUNT(*) AS total FROM `{$table}`";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int) ($row['total'] ?? 0);
}

function getAll(mysqli $conn, string $table): array
{
    $table = bvassist_assert_table_name($table);

    $orderColumn = bvassist_has_column($conn, $table, 'created_at') ? '`created_at`' : '`id`';
    $sql = "SELECT * FROM `{$table}` ORDER BY {$orderColumn} DESC";
    $result = mysqli_query($conn, $sql);
    if ($result !== false) {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    return bvassist_fetch_rows($conn, "SELECT * FROM `{$table}`");
}

function insertData(mysqli $conn, string $table, array $data): bool
{
    $table = bvassist_assert_table_name($table);

    if (empty($data)) {
        return false;
    }

    $columns = array_keys($data);
    $placeholders = [];
    $types = '';
    $values = [];

    foreach ($data as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } elseif (is_null($value)) {
            $types .= 's';
            $value = null;
        } else {
            $types .= 's';
        }

        $placeholders[] = '?';
        $values[] = $value;
    }

    $columnSql = '`' . implode('`, `', array_map('strval', $columns)) . '`';
    $placeholderSql = implode(', ', $placeholders);
    $sql = "INSERT INTO `{$table}` ({$columnSql}) VALUES ({$placeholderSql})";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    $bindValues = [$types];
    foreach ($values as $index => $value) {
        $bindValues[] = &$values[$index];
    }

    if (!call_user_func_array([$stmt, 'bind_param'], $bindValues)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function getRecent(mysqli $conn, string $table, int $limit = 5): array
{
    $table = bvassist_assert_table_name($table);
    $limit = max(1, (int) $limit);

    $orderColumn = bvassist_has_column($conn, $table, 'created_at') ? '`created_at`' : '`id`';
    $sql = "SELECT * FROM `{$table}` ORDER BY {$orderColumn} DESC LIMIT {$limit}";
    $result = mysqli_query($conn, $sql);
    if ($result !== false) {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    return array_slice(getAll($conn, $table), 0, $limit);
}

function searchRecords(mysqli $conn, string $table, string $search, array $columns): array
{
    $table = bvassist_assert_table_name($table);
    $search = trim($search);

    $validColumns = [];
    foreach ($columns as $column) {
        if (preg_match('/^[A-Za-z0-9_]+$/', (string) $column)) {
            $validColumns[] = $column;
        }
    }

    if (empty($validColumns)) {
        return [];
    }

    if ($search === '') {
        return getRecent($conn, $table, 5);
    }

    $conditions = [];
    $types = '';
    $values = [];
    $searchValue = '%' . $search . '%';

    foreach ($validColumns as $column) {
        $conditions[] = "`{$column}` LIKE ?";
        $types .= 's';
        $values[] = $searchValue;
    }

    $orderColumn = bvassist_has_column($conn, $table, 'created_at') ? '`created_at`' : '`id`';
    $sql = "SELECT * FROM `{$table}` WHERE (" . implode(' OR ', $conditions) . ") ORDER BY {$orderColumn} DESC, `id` DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$values);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return [];
    }

    $rows = bvassist_fetch_stmt_rows($stmt);
    mysqli_stmt_close($stmt);

    return $rows;
}
