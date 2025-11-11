<?php
header('Content-Type: application/json; charset=utf-8');
try {
  $pdo = new PDO('mysql:host=127.0.0.1;dbname=nhathuocankhang;charset=utf8mb4','root','',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch(Throwable $e){ http_response_code(500); echo json_encode(['error'=>'DB connect fail']); exit; }

$method = $_SERVER['REQUEST_METHOD'];
if ($method==='POST' &&
   (strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '')==='PUT' || ($_POST['_method'] ?? '')==='PUT')) {
  $method='PUT';
}
if ($method!=='PUT'){ http_response_code(405); echo json_encode(['error'=>'Method Not Allowed']); exit; }

$raw = file_get_contents('php://input');
$data = json_decode($raw,true);
if(!is_array($data)){ http_response_code(400); echo json_encode(['error'=>'Body phải là JSON']); exit; }

$sodh   = (int)($data['sodh'] ?? 0);          // 0 = tạo mới, >0 = ghi đè chi tiết
$manv   = (int)($data['manv'] ?? 1);
$giam   = (float)($data['giagiam'] ?? 0);
$items  = $data['items'] ?? [];

if (!$items || !is_array($items)){
  http_response_code(400); echo json_encode(['error'=>'Thiếu items']); exit;
}

try{
  $pdo->beginTransaction();

  if ($sodh<=0){
    $st=$pdo->prepare("INSERT INTO donhang(manv, giagiam) VALUES(:manv, :giam)");
    $st->execute([':manv'=>$manv, ':giam'=>$giam]);
    $sodh = (int)$pdo->lastInsertId();
  } else {
    $pdo->prepare("UPDATE donhang SET manv=:manv, giagiam=:giam WHERE sodh=:sodh")
        ->execute([':manv'=>$manv, ':giam'=>$giam, ':sodh'=>$sodh]);
    $pdo->prepare("DELETE FROM chitietdh WHERE sodh=:sodh")->execute([':sodh'=>$sodh]);
  }

  $tong = 0;
  $ins = $pdo->prepare("INSERT INTO chitietdh(sodh, masp, sl, gia) VALUES(:sodh,:masp,:sl,:gia)");
  $get = $pdo->prepare("SELECT giaban FROM sanpham WHERE masp=:masp");

  foreach($items as $it){
    $masp = (int)($it['masp'] ?? 0);
    $sl   = max(1, (int)($it['sl'] ?? 1));
    if ($masp<=0) continue;

    $get->execute([':masp'=>$masp]);
    $gia = (float)$get->fetchColumn();
    if ($gia===false){ throw new Exception("masp $masp không tồn tại"); }

    $ins->execute([':sodh'=>$sodh, ':masp'=>$masp, ':sl'=>$sl, ':gia'=>$gia]);
    $tong += $sl*$gia;
  }

  $tong -= $giam;
  $pdo->commit();

  // Trả đơn vừa lưu
  $rows = $pdo->prepare(
    "SELECT c.masp, sp.tensp, c.sl, c.gia, (c.sl*c.gia) AS thanhtien
     FROM chitietdh c JOIN sanpham sp ON sp.masp=c.masp WHERE c.sodh=:sodh");
  $rows->execute([':sodh'=>$sodh]);
  echo json_encode(['sodh'=>$sodh,'tongtien'=>$tong,'items'=>$rows->fetchAll()],
    JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

} catch(Throwable $e){
  if($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['error'=>'Save fail','detail'=>$e->getMessage()]);
}
