<?php
header('Content-Type: application/json; charset=utf-8');
try {
  $pdo = new PDO('mysql:host=127.0.0.1;dbname=nhathuocankhang;charset=utf8mb4','root','',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch(Throwable $e){ http_response_code(500); echo json_encode(['error'=>'DB connect fail']); exit; }

echo json_encode(
  $pdo->query("SELECT manv, hoten FROM nhanvien ORDER BY hoten")->fetchAll(),
  JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
);
