<?php
header('Content-Type: application/json; charset=utf-8');
try{
  $pdo=new PDO('mysql:host=127.0.0.1;dbname=nhathuocankhang;charset=utf8mb4','root','',[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
  ]);
}catch(Throwable $e){ http_response_code(500); echo json_encode(['error'=>'DB connect fail']); exit; }

$m=$_SERVER['REQUEST_METHOD'];
if($m==='POST' && strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '')==='PUT') $m='PUT';
if($m!=='PUT'){ http_response_code(405); echo json_encode(['error'=>'Method Not Allowed']); exit; }

$raw=file_get_contents('php://input'); $d=json_decode($raw,true);
if(!is_array($d)){ http_response_code(400); echo json_encode(['error'=>'Body phải là JSON']); exit; }

$manv  = (int)($d['manv'] ?? 0);              // 0 hoặc âm -> tạo mới
$hoten = trim($d['hoten'] ?? '');
$gt    = $d['gt'] ?? 'Nam';                   // 'Nam'|'Nữ'|'Khác'
$ns    = $d['ns'] ?? null;                    // YYYY-MM-DD hoặc null
$ngayvl= $d['ngayvl'] ?? null;

if($hoten===''){ http_response_code(400); echo json_encode(['error'=>'Thiếu hoten']); exit; }
if(!in_array($gt,['Nam','Nữ','Khác'],true)){ http_response_code(400); echo json_encode(['error'=>'gt không hợp lệ']); exit; }

try{
  if($manv<=0){
    $st=$pdo->prepare("INSERT INTO nhanvien(hoten,gt,ns,ngayvl) VALUES(:hoten,:gt,:ns,:ngayvl)");
    $st->execute([':hoten'=>$hoten,':gt'=>$gt,':ns'=>$ns,':ngayvl'=>$ngayvl]);
    $manv=(int)$pdo->lastInsertId();
  }else{
    $st=$pdo->prepare("UPDATE nhanvien SET hoten=:hoten, gt=:gt, ns=:ns, ngayvl=:ngayvl WHERE manv=:manv");
    $st->execute([':hoten'=>$hoten,':gt'=>$gt,':ns'=>$ns,':ngayvl'=>$ngayvl,':manv'=>$manv]);
  }
  $st=$pdo->prepare("SELECT manv,hoten,gt,ns,ngayvl FROM nhanvien WHERE manv=:id");
  $st->execute([':id'=>$manv]);
  echo json_encode(['saved'=>$st->fetch()], JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
  http_response_code(500); echo json_encode(['error'=>'Save fail','detail'=>$e->getMessage()]);
}
