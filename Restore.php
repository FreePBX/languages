<?php
namespace FreePBX\modules\Languages;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore($jobid){
		$configs = $this->getConfigs();
		$this->processConfigs($configs);
	}

		public function processLegacy($pdo, $data, $tables, $unknownTables){
				$this->restoreLegacyDatabase($pdo);
		}
		public function processConfigs($configs){
				foreach ($configs['languages'] as $language) {
						$this->FreePBX->Languages->editLanguage($language['language_id'], $language['description'], $language['lang_code'], $language['dest']);
				}
				foreach ($configs['incoming'] as $incoming) {
						$this->FreePBX->Languages->updateIncoming($incoming['language'], $incoming['extension'], $incoming['cidnum']);
				}
				foreach ($configs['users'] as $user => $lang) {
						$this->FreePBX->Languages->updateUserLanguage($user, $lang);
				}
		}
}