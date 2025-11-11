<?php
header('Content-Type: application/json; charset=utf-8');
try {
  $pdo = new PDO('mysql:host=127.0.0.1;dbname=nhathuocankhang;charset=utf8mb4','root','',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch(Throwable $e){ http_response_code(500); echo json_encode(['error'=>'DB connect fail']); exit; }

$q     = trim($_GET['q'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
$offset= ($page-1)*$limit;

$where = '1';
$params = [];
if ($q!==''){ $where = '(sp.tensp LIKE :q)'; $params[':q']="%$q%"; }

$sqlCount = "SELECT COUNT(*) FROM sanpham sp WHERE $where";
$st = $pdo->prepare($sqlCount); $st->execute($params); $total = (int)$st->fetchColumn();

$sql = "SELECT sp.masp, sp.tensp, sp.giaban, dv.tendv
        FROM sanpham sp
        LEFT JOIN donvitinh dv ON sp.madv=dv.madv
        WHERE $where
        ORDER BY sp.tensp
        LIMIT :limit OFFSET :offset";
$st = $pdo->prepare($sql);
foreach($params as $k=>$v) $st->bindValue($k,$v);
$st->bindValue(':limit',$limit,PDO::PARAM_INT);
$st->bindValue(':offset',$offset,PDO::PARAM_INT);
$st->execute();
echo json_encode(['page'=>$page,'limit'=>$limit,'total'=>$total,'items'=>$st->fetchAll()],
  JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
