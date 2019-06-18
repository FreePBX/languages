<?php
namespace FreePBX\modules\Languages;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
    $configs = [
        'languages' => $this->FreePBX->Languages->listLanguages(),
        'incoming' =>  $this->FreePBX->Languages->getIncoming(),
        'users' => $this->FreePBX->Languages->getAllUserLanguages(),
    ];

    $this->addDependency('core');
    $this->addConfigs($configs);
  }
}
