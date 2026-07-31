<?php
/**
 * AeroBook – Database Utility Helpers
 *
 * General-purpose database operations: insert, update, delete, count.
 * All functions use the global $conn connection from config.php.
 */

if (!defined('AEROBOOK_DB_HELPER')) {

function dbInsert($sql, $types, ...$params) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $id;
}

function dbUpdate($sql, $types, ...$params) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_affected_rows($conn);
    mysqli_stmt_close($stmt);
    return $affected;
}

function deleteById($table, $id, $idColumn = 'id') {
    global $conn;
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $idColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $idColumn);
    $stmt = mysqli_prepare($conn, "DELETE FROM {$table} WHERE {$idColumn} = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function countWhere($table, $column = null, $value = null) {
    global $conn;
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if ($column !== null && $value !== null) {
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM {$table} WHERE {$column} = ?");
        mysqli_stmt_bind_param($stmt, "s", $value);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return (int)$row['c'];
    }
    $result = mysqli_query($conn, "SELECT COUNT(*) as c FROM {$table}");
    $row = mysqli_fetch_assoc($result);
    return (int)$row['c'];
}

define('AEROBOOK_DB_HELPER', true);
}
