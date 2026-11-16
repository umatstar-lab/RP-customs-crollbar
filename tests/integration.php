<?php
$_SERVER['HTTP_HOST']='localhost'; $_SERVER['REQUEST_URI']='/'; $_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['PHP_SELF']='/index.php';
define('_JEXEC', 1);
if (getenv('SCROLLBAR_TEST_DB') !== '1' || !getenv('JOOMLA_ROOT')) {
    throw new RuntimeException('Use a disposable Joomla installation: set JOOMLA_ROOT and SCROLLBAR_TEST_DB=1.');
}
define('JPATH_BASE', rtrim(getenv('JOOMLA_ROOT'), '/'));
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Document\JsonDocument;
use Joomla\CMS\Event\Application\BeforeCompileHeadEvent;
use Joomla\CMS\Form\Form;
use Joomla\Plugin\System\RPCustomScrollbar\Support\Settings;
use Joomla\Registry\Registry;
use Joomla\Database\DatabaseInterface;
$client = $argv[1] ?? 'site';
$container = Factory::getContainer();
$service = $client === 'site' ? 'session.web.site' : 'session.web.administrator';
$container->alias('session', $service)->alias(Session::class,$service)->alias(Joomla\Session\Session::class,$service)->alias(Joomla\Session\SessionInterface::class,$service);
$app = $container->get($client === 'site' ? SiteApplication::class : AdministratorApplication::class);
Factory::$application = $app;
$app->createExtensionNamespaceMap();
$checks=0;
function check($condition, $message) {global $checks; if (!$condition) {throw new RuntimeException($message);} $checks++;}
$db=$container->get(DatabaseInterface::class);
$db->setQuery("UPDATE #__extensions SET enabled=0 WHERE type='plugin' AND folder='behaviour' AND element IN ('compat','compat6')")->execute();
$plugin = $app->bootPlugin('rpcustomscrollbar','system');
check($plugin instanceof Joomla\Plugin\System\RPCustomScrollbar\Extension\RPCustomScrollbar,'modern provider boot');
check($plugin instanceof Joomla\Event\SubscriberInterface,'subscriber');
// Register and dispatch via Joomla's actual dispatcher, rather than calling the handler directly.
$app->getDispatcher()->addSubscriber($plugin);
foreach (range(0,7) as $mask) {
 $params=['tksborder'=>$mask&1,'tbsborder'=>($mask>>1)&1,'tbhsborder'=>($mask>>2)&1,'tkaborder'=>$mask&1,'tbaborder'=>($mask>>1)&1,'tbhaborder'=>($mask>>2)&1];
 $plugin->params=new Registry($params);$doc=new HtmlDocument();
 $event=new BeforeCompileHeadEvent('onBeforeCompileHead',['subject'=>$app,'document'=>$doc]);
 $app->getDispatcher()->dispatch('onBeforeCompileHead',$event);
 $assets=$doc->getWebAssetManager()->getAssets('style',true);$css=$assets['plg_system_rpcustomscrollbar']->getOption('content')??'';
 check($css!=='' && substr_count($css,'{')===substr_count($css,'}'),'balanced CSS '.$mask);
}
foreach ([['site'=>0,'admin'=>0],['site'=>false,'admin'=>false]] as $params) {
 $plugin->params=new Registry($params);$doc=new HtmlDocument();
 $plugin->onBeforeCompileHead(new BeforeCompileHeadEvent('onBeforeCompileHead',['subject'=>$app,'document'=>$doc]));
 check(!$doc->getWebAssetManager()->getAssets('style',true),'disabled');
}
$plugin->params=new Registry();$json=new JsonDocument();
$plugin->onBeforeCompileHead(new BeforeCompileHeadEvent('onBeforeCompileHead',['subject'=>$app,'document'=>$json]));
check(!$json->getWebAssetManager()->getAssets('style',true),'JSON untouched');
$payloads=['12px;}body{display:none}/*','</style><script>alert(1)</script>','#fff;background:url(https://example.test/x)','expression(alert(1))','calc(100px)','-1px','100000px','1px!important',"12px\n}",[],new stdClass(),null,INF,NAN];
foreach (Settings::defaults() as $name=>$default) {
 foreach ($payloads as $bad) {
  $p=Settings::clean([$name=>$bad]);check($p[$name]===$default,'fallback '.$name);
  $css=Settings::css([$name=>$bad],$client==='site'?'site':'administrator');
  check(!str_contains($css,'<')&&!str_contains($css,'url(')&&!str_contains($css,'expression('),'safe output '.$name);
 }
}
check(Settings::normalize('tkswidth','1.5rem')==='1.5rem','valid rem');
check(Settings::normalize('tkswidth','12')==='12px','unitless fallback');
check(Settings::normalize('tksbgcolor','#AbC')==='#abc','short hex');
check(Settings::normalize('tkswidth','0px')==='0','zero');
$form=new Form('scrollbar-test');
check($form->loadFile(JPATH_PLUGINS.'/system/rpcustomscrollbar/rpcustomscrollbar.xml',false,'/extension/config'),'load settings form');
$data=$form->filter(['params'=>['tkswidth'=>$payloads[0],'tkabgcolor'=>$payloads[1],'tksbstyle'=>'solid;}body{display:none}','site'=>'1']]);
check($data['params']['tkswidth']==='12px','save width filtering');
check($data['params']['tkabgcolor']==='#ffffff','save color filtering');
check($data['params']['tksbstyle']==='solid','save enum filtering');
check($data['params']['site']===1,'save boolean filtering');
$doc=new HtmlDocument();$doc->cspNonce='audit-test-nonce';$plugin->params=new Registry();
$plugin->onBeforeCompileHead(new BeforeCompileHeadEvent('onBeforeCompileHead',['subject'=>$app,'document'=>$doc]));
$renderer=new Joomla\CMS\Document\Renderer\Html\StylesRenderer($doc);
$html=$renderer->render('');check(str_contains($html,'nonce="audit-test-nonce"'),'CSP nonce');
check(str_contains($html,'::-webkit-scrollbar'),'rendered style');
echo json_encode(['joomla'=>JVERSION,'php'=>PHP_VERSION,'client'=>$client,'checks'=>$checks,'status'=>'PASS'])."\n";
