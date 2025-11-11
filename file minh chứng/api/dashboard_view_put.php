<?php
header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=nhathuocankhang;charset=utf8mb4',
    'root', '',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
  );
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'DB connect fail','detail'=>$e->getMessage()]);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method==='POST' && strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '')==='PUT') $method='PUT';
if ($method!=='PUT') { http_response_code(405); echo json_encode(['error'=>'Method Not Allowed']); exit; }

$sql = "CREATE OR REPLACE VIEW v_doanhthu_ngay AS
        SELECT DATE(d.ngaytao) AS ngay, SUM(c.sl*c.gia) - SUM(d.giagiam) AS doanhthu
        FROM donhang d JOIN chitietdh c USING(sodh)
        GROUP BY DATE(d.ngaytao)";

$pdo->exec($sql);

ob_clean(); // ngăn ký tự rác
echo json_encode(['ok'=>true,'view'=>'v_doanhthu_ngay'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
exit;
