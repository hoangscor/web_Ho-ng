<?php
header('Content-Type: application/json; charset=utf-8');
try{
  $pdo=new PDO('mysql:host=127.0.0.1;dbname=nhathuocankhang;charset=utf8mb4','root','',[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
  ]);
}catch(Throwable $e){ http_response_code(500); echo json_encode(['error'=>'DB connect fail']); exit; }

$id = (int)($_GET['id'] ?? 0);
if ($id>0) {
  $st=$pdo->prepare("SELECT manv,hoten,gt,ns,ngayvl FROM nhanvien WHERE manv=:id");
  $st->execute([':id'=>$id]); echo json_encode($st->fetch() ?: new stdClass()); exit;
}

$q     = trim($_GET['q'] ?? '');
$page  = max(1,(int)($_GET['page'] ?? 1));
$limit = min(100,max(1,(int)($_GET['limit'] ?? 20)));
$offset= ($page-1)*$limit;
$sort  = $_GET['sort'] ?? 'hoten';
$dir   = strtolower($_GET['dir'] ?? 'asc'); $dir = $dir==='desc'?'DESC':'ASC';
$allowed=['manv','hoten','ns','ngayvl']; if(!in_array($sort,$allowed,true)) $sort='hoten';

$where='1'; $p=[];
if($q!==''){ $where='(hoten LIKE :q)'; $p[':q']="%$q%"; }

$st=$pdo->prepare("SELECT COUNT(*) FROM nhanvien WHERE $where"); $st->execute($p); $total=(int)$st->fetchColumn();

$sql="SELECT manv,hoten,gt,ns,ngayvl FROM nhanvien WHERE $where ORDER BY $sort $dir LIMIT :lim OFFSET :off";
$st=$pdo->prepare($sql);
foreach($p as $k=>$v) $st->bindValue($k,$v);
$st->bindValue(':lim',$limit,PDO::PARAM_INT); $st->bindValue(':off',$offset,PDO::PARAM_INT);
$st->execute();
echo json_encode(['page'=>$page,'limit'=>$limit,'total'=>$total,'items'=>$st->fetchAll()], JSON_UNESCAPED_UNICODE);
