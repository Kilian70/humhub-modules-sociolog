<?php
$root=rtrim($argv[1], '/');
$version=basename($root);
define('YII_APP_BASE_PATH',$root.'/protected');define('YII_ENV','test');define('YII_DEBUG',true);
require $root.'/protected/vendor/autoload.php';require $root.'/protected/vendor/yiisoft/yii2/Yii.php';
Yii::setAlias('@humhub',$root.'/protected/humhub');
$config=yii\helpers\ArrayHelper::merge(require $root.'/protected/humhub/config/common.php',require $root.'/protected/humhub/config/console.php',require $root.'/protected/config/common.php',require $root.'/protected/config/dynamic.php',require $root.'/protected/config/console.php');
$app=new humhub\components\console\Application($config);
$module=$app->getModule('sociolog');
if (!$module)throw new RuntimeException('Module not enabled');
if (!$app->db->schema->getTableSchema('sociolog_entry_review',true)?->getColumn('replacement_url'))throw new RuntimeException('Module migrations incomplete');
$assertions=0;
function check($condition,$message){global $assertions;if(!$condition)throw new RuntimeException($message);$assertions++;}
use humhub\modules\sociolog\notifications\NotificationCompatibility as Compatibility;
use humhub\modules\sociolog\notifications\EntryCreated;
use humhub\modules\sociolog\notifications\EntryUpdated;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\notification\components\BaseNotification;
check(!Compatibility::usesRecordApi(),'Expected native Stable API');
$classes=$module->getNotifications();check(count($classes)===2,'Exactly the two public notification classes must be discovered');
$transaction=$app->db->beginTransaction();
try {
 foreach ([EntryCreated::class,EntryUpdated::class] as $class) {
  check(in_array($class,$classes,true),'Public class discovered');
  check(is_subclass_of($class,BaseNotification::class),'Native Stable parent');
  $object=new $class();check($object->getCategory()->id==='sociolog','Native category id');
  check(get_class($class::instance())===$class,'Persistent public class name');
  check($object->isDigestable(),'Existing digest flag');
  $html=$object->render();check(is_string($html)&&strlen($html)>20,'Missing source renders');
  foreach (['A & B','Literal <tag> "title"','<img src=x onerror="alert(1)">'] as $title) {
   $entry=new Entry(['title'=>$title,'id'=>0]);$object=new $class(['source'=>$entry]);
   $subject=$object->getMailSubject();check(str_contains($subject,$title),'Subject preserves literal text');
   $html=$object->render();check(str_contains($html,yii\helpers\Html::encode($title)),'HTML encodes title');
   check(!str_contains($html,'<img src=x'),'No raw HTML injection');
   $mail=$object->html();check(str_contains($mail,yii\helpers\Html::encode($title)),'HTML mail encodes title');
   check(str_contains($object->text(),$title),'Plain mail preserves title');
  }
 }
} finally {$transaction->rollBack();}
echo "HumHub $version / PHP ".PHP_VERSION.": $assertions native notification assertions OK; no fixture reset; existing data preserved\n";
