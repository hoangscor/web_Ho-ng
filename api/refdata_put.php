<?php
// nạp db.php
$paths=[__DIR__.'/db.php', dirname(__DIR__).'/api/db.php', dirname(__DIR__).'/db.php'];
foreach($paths as $p){ if(is_file($p)){ require_once $p; break; } }
if(!function_exists('pdo')){
  define('DB_HOST','127.0.0.1'); define('DB_NAME','nhathuocankhang');
  define('DB_USER','root'); define('DB_PASS','');
  function pdo(){ static $pdo; if(!$pdo){ $pdo=new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
    DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); } return $pdo; }
}
if(!function_exists('json')){ function json($d,$c=200){ http_response_code($c); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; } }

// nhận PUT hoặc POST override
$method=$_SERVER['REQUEST_METHOD'];
if($method==='POST' && (
   (isset($_POST['_method']) && strtoupper($_POST['_method'])==='PUT') ||
   (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) && strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])==='PUT')
)){ $method='PUT'; }
if($method!=='PUT') json(['error'=>'Method Not Allowed'],405);

// đọc body (JSON | form)
$ct=$_SERVER['CONTENT_TYPE'] ?? '';
$raw=file_get_contents('php://input'); $data=[];
if(stripos($ct,'application/json')!==false){ $data=json_decode($raw,true) ?: []; }
else{ parse_str($raw,$data); if(!$data) $data=$_POST; }

$map = [
  'danhmuc'    => ['table'=>'danhmuc','id'=>'madm','name'=>'tendm'],
  'donvitinh'  => ['table'=>'donvitinh','id'=>'madv','name'=>'tendv'],
  'thuonghieu' => ['table'=>'thuonghieu','id'=>'math','name'=>'tenth'],
];

$type = strtolower(trim($data['type'] ?? ($_GET['type'] ?? '')));
$id   = (int)($data['id'] ?? ($_GET['id'] ?? 0));
$name = trim($data['name'] ?? '');

if(!isset($map[$type])) json(['error'=>'type không hợp lệ'],400);
if($id<=0) json(['error'=>'Thiếu hoặc sai id'],400);
if($name==='') json(['error'=>'Thiếu name'],400);

$m=$map[$type];

// cập nhật
try{
  $st=pdo()->prepare("UPDATE {$m['table']} SET {$m['name']}=:name WHERE {$m['id']}=:id");
  $st->execute([':name'=>$name,':id'=>$id]);
} catch(PDOException $e){
  if($e->errorInfo[1]==1062) json(['error'=>'Tên bị trùng (UNIQUE)'],409);
  json(['error'=>'DB error','detail'=>$e->getMessage()],500);
}

// trả lại bản ghi
$st=pdo()->prepare("SELECT {$m['id']} AS id, {$m['name']} AS name FROM {$m['table']} WHERE {$m['id']}=:id");
$st->execute([':id'=>$id]);
json(['type'=>$type,'updated'=>$st->fetch()]);
