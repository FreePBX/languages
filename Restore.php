<?php
namespace FreePBX\modules\__MODULENAME__;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $configs = $this->getConfigs();
    foreach ($configs['languages'] as $language) {
        $this->FreePBX->Languages->editLanguage($language['language_id'], $language['description'], $language['lang_code'], $language['dest']);
    }
    foreach ($configs['incoming'] as $incoming) {
        $this->FreePBX->Languages->updateIncoming($incoming['language'], $incoming['extension'],$incoming['cidnum']);
    }
    foreach ($configs['users'] as $user => $lang) {
        $this->FreePBX->Languages->updateUserLanguage($user, $lang);
    }
  }
}