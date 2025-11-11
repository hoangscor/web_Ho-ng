<?php
// nạp db.php (ưu tiên cùng thư mục), nếu không có thì fallback tự kết nối
$paths = [
  __DIR__.'/db.php',
  dirname(__DIR__).'/api/db.php',
  dirname(__DIR__).'/db.php'
];
foreach ($paths as $p) { if (is_file($p)) { require_once $p; break; } }
if (!function_exists('pdo')) {
  define('DB_HOST','127.0.0.1'); define('DB_NAME','nhathuocankhang');
  define('DB_USER','root'); define('DB_PASS','');
  function pdo() {
    static $pdo=null;
    if ($pdo===null) {
      $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS, [
          PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES=>false
        ]);
    }
    return $pdo;
  }
}
if (!function_exists('json')) {
  function json($d,$c=200){ http_response_code($c); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
}

$q     = isset($_GET['q']) ? trim($_GET['q']) : '';
$madm  = isset($_GET['madm']) && $_GET['madm']!=='' ? (int)$_GET['madm'] : null;
$madv  = isset($_GET['madv']) && $_GET['madv']!=='' ? (int)$_GET['madv'] : null;
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset= ($page-1)*$limit;

$sort  = $_GET['sort'] ?? 'masp';
$dir   = strtolower($_GET['dir'] ?? 'asc');
$allowedSort = ['masp','tensp','giaban','giagiam'];
if (!in_array($sort,$allowedSort,true)) $sort='masp';
$dir = $dir==='desc' ? 'DESC' : 'ASC';

$where = ['1']; $params = [];
if ($q!==''){ $where[]='(sp.tensp LIKE :q OR dm.tendm LIKE :q)'; $params[':q']="%$q%"; }
if ($madm!==null){ $where[]='sp.madm=:madm'; $params[':madm']=$madm; }
if ($madv!==null){ $where[]='sp.madv=:madv'; $params[':madv']=$madv; }
$whereSql = implode(' AND ', $where);

$st = pdo()->prepare("SELECT COUNT(*) FROM sanpham sp LEFT JOIN danhmuc dm ON sp.madm=dm.madm WHERE $whereSql");
$st->execute($params);
$total = (int)$st->fetchColumn();

$sql = "SELECT sp.masp, sp.tensp, sp.giaban, sp.giagiam, sp.hinhsp,
               sp.xuatxu, sp.congdung, sp.cachdung,
               sp.madm, dm.tendm, sp.madv, dv.tendv, sp.math
        FROM sanpham sp
        LEFT JOIN danhmuc dm ON sp.madm=dm.madm
        LEFT JOIN donvitinh dv ON sp.madv=dv.madv
        WHERE $whereSql
        ORDER BY $sort $dir
        LIMIT :limit OFFSET :offset";
$st = pdo()->prepare($sql);
foreach($params as $k=>$v){ $st->bindValue($k,$v); }
$st->bindValue(':limit',$limit,PDO::PARAM_INT);
$st->bindValue(':offset',$offset,PDO::PARAM_INT);
$st->execute();
json(['page'=>$page,'limit'=>$limit,'total'=>$total,'items'=>$st->fetchAll()]);
