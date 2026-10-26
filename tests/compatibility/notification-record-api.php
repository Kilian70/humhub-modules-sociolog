<?php
namespace humhub\modules\notification\components {
    // Capture only the dispatch boundary; no Develop installation/database is created.
    class NotificationManager {
        public static array $calls = [];
        public static function dispatch(...$args): void {self::$calls[]=$args;}
    }
}
namespace {
$root=$argv[1];$core=$argv[2];$source=$argv[3] ?? dirname(__DIR__, 2);
require $root.'/protected/vendor/autoload.php';require $root.'/protected/vendor/yiisoft/yii2/Yii.php';
Yii::setAlias('@humhub',$root.'/protected/humhub');
Yii::setAlias('@humhub/modules/sociolog',$source);
require $core.'/NotificationPriority.php';
require $core.'/protected/humhub/modules/notification/components/NotificationCategory.php';
require $core.'/protected/humhub/modules/notification/components/BaseNotification.php';
class ContractModule extends yii\base\Module {public function getName(){return 'Logbuch';}}
class ContractModuleManager extends yii\base\Component {
 public function getModuleIdByClass($class){return 'sociolog';}
 public function getModule($id,$throw=true){return Yii::$app->getModule($id);}
}
$app=new yii\console\Application(['id'=>'source-contract-only','basePath'=>'/tmp','sourceLanguage'=>'de','language'=>'de','components'=>['i18n'=>['translations'=>['*'=>['class'=>yii\i18n\PhpMessageSource::class,'sourceLanguage'=>'de','basePath'=>'/tmp']]],'moduleManager'=>['class'=>ContractModuleManager::class]],'modules'=>['sociolog'=>['class'=>ContractModule::class]]]);
$assertions=0;
function check($condition,$label){global $assertions;if(!$condition)throw new \RuntimeException($label);$assertions++;}
use humhub\modules\sociolog\notifications\EntryCreated;
use humhub\modules\sociolog\notifications\EntryUpdated;
use humhub\modules\sociolog\notifications\NotificationCompatibility;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\notification\components\NotificationCategory;
check(NotificationCompatibility::usesRecordApi(),'Recognizes native static category');
foreach ([EntryCreated::class,EntryUpdated::class] as $class) {
 check(is_subclass_of($class,BaseNotification::class),'Loads against actual Develop BaseNotification');
 check(!(new ReflectionClass($class))->isAbstract(),'All native abstract hooks implemented');
 check((new ReflectionMethod($class,'category'))->isStatic(),'Static category');
 $category=$class::category();check($category instanceof NotificationCategory&&$category->id==='sociolog','Real final category factory');
 $object=(new ReflectionClass($class))->newInstanceWithoutConstructor();
 (new ReflectionProperty(BaseNotification::class,'content'))->setValue($object,null);
 $params=(new ReflectionMethod($object,'getMessageParams'))->invoke($object);
 check($params['module']==='Logbuch'&&$params['title']==='Eintrag','Native parameters and missing source fallback');
 $params['title']='A & B <literal>';
 $message=(new ReflectionMethod($object,'getMessage'))->invoke($object,$params);
 check(str_contains($message,$params['title']),'Plain sentence preserves literal input for the native channel renderer');
 $subject=(new ReflectionMethod($object,'getMailSubject'))->invoke($object,$params);
 check($subject===$message,'Native mail subject signature and fallback');
 $recipient=(new ReflectionClass(humhub\modules\user\models\User::class))->newInstanceWithoutConstructor();
 check(!$object->canReceive($recipient),'Missing source denied');
 $entry=(new ReflectionClass(humhub\modules\sociolog\models\Entry::class))->newInstanceWithoutConstructor();
 NotificationCompatibility::send($class,$entry,$recipient,$recipient);
 $call=humhub\modules\notification\components\NotificationManager::$calls[array_key_last(humhub\modules\notification\components\NotificationManager::$calls)];
 check($call[0]===$class&&$call[1]===$recipient&&$call[2]===$entry&&$call[3]===$recipient,'Actual native static send retains public class, recipient, source and actor');
}
check(!is_subclass_of(humhub\modules\sociolog\notifications\SociologNotificationCategory::class,BaseNotification::class),'Module discovery safely loads final category alias');
echo "Actual Develop core source contract: $assertions assertions OK; dispatch captured, no Develop database or installation\n";
}
