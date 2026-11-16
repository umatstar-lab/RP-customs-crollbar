<?php
define('_JEXEC', 1);
require __DIR__ . '/../src/Support/Settings.php';
use Joomla\Plugin\System\RPCustomScrollbar\Support\Settings;
$checks=0;
function verify($ok, $message) {global $checks; if(!$ok)throw new RuntimeException($message); $checks++;}
$malicious=['12px;}body{display:none}/*','</style><script>alert(1)</script>','#fff;background:url(https://example.test/track)','javascript:alert(1)','expression(alert(1))','calc(1px)','var(--x)','-1px','1e8px','9999px','1px!important',"1px\0",'1px;','1px/**/','\\31 px',[],new stdClass(),null,true,false,100];
foreach(Settings::defaults() as $name=>$default){
 foreach($malicious as $value){
  $safe=Settings::normalize($name,$value);
  verify(is_string($safe)||is_int($safe),'scalar result');
  if(is_string($safe))verify(preg_match('/\A[a-z0-9.#%]+\z/D',$safe)===1,'safe token '.$name);
  foreach(['site','administrator'] as $client){
   $css=Settings::css([$name=>$value],$client);
   verify(!str_contains($css,'<')&&!str_contains($css,'url(')&&!str_contains($css,'expression('),'no injection');
  }
 }
}
$css=[];
foreach(['site','administrator'] as $client){
 foreach(range(0,7) as $mask){
  $p=[];foreach(['s','a'] as $suffix){foreach(['tk','tb','tbh'] as $i=>$prefix)$p[$prefix.$suffix.'border']=($mask>>$i)&1;}
  $style=Settings::css($p,$client);verify(substr_count($style,'{')===substr_count($style,'}'),'balanced');$css[]=$style;
 }
}
foreach(['12px','0','0px','1.5em','2rem','100%','.5px',' 12PX '] as $value)verify(Settings::normalize('tkswidth',$value)!=='','valid length');
foreach(['-1px','201px','11rem','101%','5','999999999999999999px'] as $value)verify(Settings::normalize('tkswidth',$value)==='12px','invalid length');
verify(Settings::css(['site'=>0],'site')==='','site switch');
verify(Settings::css(['admin'=>0],'administrator')==='','admin switch');
verify(Settings::css([],'api')==='','API excluded');
echo json_encode(['php'=>PHP_VERSION,'checks'=>$checks,'status'=>'PASS','css'=>$css],JSON_UNESCAPED_SLASHES)."\n";
