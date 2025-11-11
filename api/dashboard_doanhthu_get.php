<?php
header('Content-Type: application/json; charset=utf-8');
try{
  $pdo=new PDO('mysql:host=127.0.0.1;dbname=nhathuocankhang;charset=utf8mb4','root','',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
}catch(Throwable $e){ http_response_code(500); echo json_encode(['error'=>'DB connect fail']); exit; }

$days   = max(1, (int)($_GET['days'] ?? 30));
$months = max(1, (int)($_GET['months'] ?? 12));

$fromDay = (new DateTime())->modify("-$days day")->format('Y-m-d');
$fromMon = (new DateTime('first day of -'.($months-1).' month'))->format('Y-m-d');
$today   = (new DateTime())->format('Y-m-d');
$mon0    = (new DateTime('first day of this month'))->format('Y-m-d');

function hasView($pdo,$name){
  $st=$pdo->prepare("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:n");
  $st->execute([':n'=>$name]); return (int)$st->fetchColumn()>0;
}

if(hasView($pdo,'v_doanhthu_ngay')){
  $st=$pdo->prepare("SELECT ngay, doanhthu FROM v_doanhthu_ngay WHERE ngay>=:f ORDER BY ngay");
  $st->execute([':f'=>$fromDay]); $byDay=$st->fetchAll();
}else{
  $st=$pdo->prepare("SELECT DATE(d.ngaytao) ngay, SUM(c.sl*c.gia)-SUM(d.giagiam) doanhthu
                     FROM donhang d JOIN chitietdh c USING(sodh)
                     WHERE DATE(d.ngaytao)>=:f GROUP BY DATE(d.ngaytao) ORDER BY ngay");
  $st->execute([':f'=>$fromDay]); $byDay=$st->fetchAll();
}

$st=$pdo->prepare("SELECT DATE_FORMAT(d.ngaytao,'%Y-%m') ym, SUM(c.sl*c.gia)-SUM(d.giagiam) doanhthu
                   FROM donhang d JOIN chitietdh c USING(sodh)
                   WHERE DATE(d.ngaytao)>=:f GROUP BY ym ORDER BY ym");
$st->execute([':f'=>$fromMon]); $byMon=$st->fetchAll();

$st=$pdo->prepare("SELECT COALESCE(SUM(c.sl*c.gia)-SUM(d.giagiam),0) FROM donhang d JOIN chitietdh c USING(sodh) WHERE DATE(d.ngaytao)=:t");
$st->execute([':t'=>$today]); $revToday=(float)$st->fetchColumn();

$st=$pdo->prepare("SELECT COALESCE(SUM(c.sl*c.gia)-SUM(d.giagiam),0) FROM donhang d JOIN chitietdh c USING(sodh) WHERE DATE(d.ngaytao)>=:m0");
$st->execute([':m0'=>$mon0]); $revMonth=(float)$st->fetchColumn();

echo json_encode(['rangeDays'=>$days,'rangeMonths'=>$months,'today'=>$revToday,'thisMonth'=>$revMonth,'byDay'=>$byDay,'byMonth'=>$byMon],
  JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
