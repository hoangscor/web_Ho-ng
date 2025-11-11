<?php
header('Content-Type: application/json; charset=utf-8');
try{
  $pdo=new PDO('mysql:host=127.0.0.1;dbname=nhathuocankhang;charset=utf8mb4','root','',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
}catch(Throwable $e){ http_response_code(500); echo json_encode(['error'=>'DB connect fail']); exit; }

$to   = $_GET['to']   ?? (new DateTime())->format('Y-m-d');
$from = $_GET['from'] ?? (new DateTime())->modify('-30 day')->format('Y-m-d');
$limit= min(50, max(1,(int)($_GET['limit'] ?? 10)));

$st=$pdo->prepare("SELECT sp.masp, sp.tensp, SUM(c.sl) sl, SUM(c.sl*c.gia) doanhthu
                   FROM chitietdh c
                   JOIN donhang d ON d.sodh=c.sodh
                   JOIN sanpham sp ON sp.masp=c.masp
                   WHERE DATE(d.ngaytao) BETWEEN :f AND :t
                   GROUP BY sp.masp, sp.tensp
                   ORDER BY sl DESC, doanhthu DESC
                   LIMIT :lim");
$st->bindValue(':f',$from); $st->bindValue(':t',$to); $st->bindValue(':lim',$limit,PDO::PARAM_INT);
$st->execute();
echo json_encode(['from'=>$from,'to'=>$to,'items'=>$st->fetchAll()], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
