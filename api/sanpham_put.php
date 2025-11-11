<?php
// load db.php
$paths=[__DIR__.'/db.php', dirname(__DIR__).'/api/db.php', dirname(__DIR__).'/db.php'];
foreach($paths as $p){ if(is_file($p)){ require_once $p; break; } }
if(!function_exists('pdo')){
  define('DB_HOST','127.0.0.1'); define('DB_NAME','nhathuocankhang'); define('DB_USER','root'); define('DB_PASS','');
  function pdo(){ static $pdo; if(!$pdo){ $pdo=new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); } return $pdo; }
}
if(!function_exists('json')){ function json($d,$c=200){ http_response_code($c); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; } }

// chấp nhận PUT; hoặc POST override
$method=$_SERVER['REQUEST_METHOD'];
if($method==='POST'){
  if(isset($_POST['_method']) && strtoupper($_POST['_method'])==='PUT') $method='PUT';
  if(isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) && strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])==='PUT') $method='PUT';
}
if($method!=='PUT') json(['error'=>'Method Not Allowed'],405);

// đọc body (JSON hoặc x-www-form-urlencoded)
$ct=$_SERVER['CONTENT_TYPE'] ?? '';
$raw=file_get_contents('php://input');
$data=[];
if(stripos($ct,'application/json')!==false){
  $data=json_decode($raw,true) ?: [];
}elseif(stripos($ct,'application/x-www-form-urlencoded')!==false){
  parse_str($raw,$data);
}
// ưu tiên masp trong body, sau đó query
$masp = isset($data['masp']) ? (int)$data['masp'] : (int)($_GET['masp'] ?? 0);
if($masp<=0) json(['error'=>'Thiếu hoặc sai masp'],400);

// whitelist field
$fields=['tensp','giaban','giagiam','hinhsp','congdung','xuatxu','cachdung','madm','madv','math'];
$set=[]; $params=[':masp'=>$masp];
foreach($fields as $f){
  if(array_key_exists($f,$data)){
    $set[]="sp.$f = :$f";
    if(in_array($f,['madm','madv','math'],true))       $params[":$f"]=($data[$f]===''?null:(int)$data[$f]);
    elseif(in_array($f,['giaban','giagiam'],true))      $params[":$f"]=(float)$data[$f];
    else                                                $params[":$f"]=$data[$f];
  }
}
if(!$set) json(['error'=>'Không có trường nào để cập nhật'],400);

// update
$st=pdo()->prepare("UPDATE sanpham sp SET ".implode(', ',$set)." WHERE sp.masp=:masp");
$st->execute($params);

// trả bản ghi sau update
$st=pdo()->prepare("SELECT sp.masp, sp.tensp, sp.giaban, sp.giagiam, sp.hinhsp,
                           sp.xuatxu, sp.congdung, sp.cachdung,
                           sp.madm, dm.tendm, sp.madv, dv.tendv, sp.math
                    FROM sanpham sp
                    LEFT JOIN danhmuc dm ON sp.madm=dm.madm
                    LEFT JOIN donvitinh dv ON sp.madv=dv.madv
                    WHERE sp.masp=:masp");
$st->execute([':masp'=>$masp]);
$item=$st->fetch(); if(!$item) json(['error'=>'Không tìm thấy masp'],404);
json(['updated'=>$item]);
