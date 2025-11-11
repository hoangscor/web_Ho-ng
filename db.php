<?php
// db.php
const DB_HOST = '127.0.0.1';
const DB_NAME = 'nhathuocankhang';
const DB_USER = 'root';
const DB_PASS = '';

function pdo() {
  static $pdo = null;
  if ($pdo === null) {
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false
    ]);
  }
  return $pdo;
}

function json($data, int $code=200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}
