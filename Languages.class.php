<?php
namespace FreePBX\modules;
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//
class Languages implements \BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}

		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
	}
	public function doConfigPageInit($page) {
		$request = $_REQUEST;
		$type = isset($request['type']) ? $request['type'] : 'setup';
		$action = isset($request['action']) ? $request['action'] :  '';
		if (isset($request['delete'])) $action = 'delete';

		$language_id = isset($request['language_id']) ? $request['language_id'] :  false;
		$description = isset($request['description']) ? $request['description'] :  '';
		$lang_code = isset($request['lang_code']) ? $request['lang_code'] :  '';
		$dest = isset($request['dest']) ? $request['dest'] :  '';
		$view = isset($request['view']) ? $request['view'] : '';
		if (isset($request['goto0']) && $request['goto0']) {
			$dest = $request[ $request['goto0'].'0' ];
		}

		switch ($action) {
			case 'add':
				$request['extdisplay'] = languages_add($description, $lang_code, $dest);
				needreload();
			break;
			case 'edit':
				languages_edit($language_id, $description, $lang_code, $dest);
				needreload();
			break;
			case 'delete':
				languages_delete($language_id);
				needreload();
			break;
		}
	}
	public function install() {

	}
	public function uninstall() {

	}
	public function backup(){

	}
	public function restore($backup){

	}
	public function getActionBar($request) {
		switch ($request['display']) {
			case 'languages':
				$buttons = array(
						'submit' => array(
							'name' => 'submit',
							'id' => 'submit',
							'value' => _("Submit")
						),
						'reset' => array(
							'name' => 'reset',
							'id' => 'reset',
							'value' => _("Reset")
						),
						'delete' => array(
							'name' => 'delete',
							'id' => 'delete',
							'value' => _("Delete")
						),
					);
				if($request['extdisplay'] == ''){
					unset($buttons['delete']);
				}
				if($request['view'] != 'form'){
					unset($buttons);
				}
				return $buttons;
			break;
		}
	}
	public function ajaxRequest($req, &$setting) {
		switch ($req) {
			case 'getJSON':
				return true;
			break;
			default:
				return false;
			break;
		}
	}
	public function ajaxHandler(){
		switch ($_REQUEST['command']) {
			case 'getJSON':
				switch ($_REQUEST['jdata']) {
					case 'grid':
						return array_values($this->listLanguages());
					break;

					default:
						return false;
					break;
				}
			break;

			default:
				return false;
			break;
		}
	}
	public function listLanguages(){
		$sql = "SELECT language_id, description, lang_code, dest FROM languages ORDER BY description ";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$results = $stmt->fetchall(\PDO::FETCH_ASSOC);
		return $results;
	}
	public function getRightNav($request) {
	  if(isset($request['view']) && $request['view'] == 'form'){
	    return load_view(__DIR__."/views/bootnav.php",array());
	  }
	}
}
