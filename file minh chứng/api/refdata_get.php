<?php
// File độc lập, KHÔNG require db.php
header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=nhathuocankhang;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
  );
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'DB connect fail','detail'=>$e->getMessage()], JSON_UNESCAPED_UNICODE); exit;
}

$map = [
  'danhmuc'    => ['table'=>'danhmuc','id'=>'madm','name'=>'tendm'],
  'donvitinh'  => ['table'=>'donvitinh','id'=>'madv','name'=>'tendv'],
  'thuonghieu' => ['table'=>'thuonghieu','id'=>'math','name'=>'tenth'],
];

$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';

if ($type) {
  if (!isset($map[$type])) { http_response_code(400); echo json_encode(['error'=>'type không hợp lệ']); exit; }
  $m=$map[$type];
  $sql="SELECT {$m['id']} AS id, {$m['name']} AS name FROM {$m['table']} ORDER BY {$m['name']}";
  $items = $pdo->query($sql)->fetchAll();
  echo json_encode(['type'=>$type,'items'=>$items], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

$out=[];
foreach($map as $k=>$m){
  $sql="SELECT {$m['id']} AS id, {$m['name']} AS name FROM {$m['table']} ORDER BY {$m['name']}";
  $out[$k]=$pdo->query($sql)->fetchAll();
}
echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
