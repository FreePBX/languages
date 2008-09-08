<?php

function languages_destinations() {
	global $module_page;

	// it makes no sense to point at another queueprio (and it can be an infinite loop)
	if ($module_page == 'languages') {
		return false;
	}

	// return an associative array with destination and description
	foreach (languages_list() as $row) {
		$extens[] = array('destination' => 'app-languages,' . $row['language_id'] . ',1', 'description' => $row['description']);
	}
	return $extens;
}

function languages_getdest($exten) {
	return array('app-languages,'.$exten.',1');
}

function languages_getdestinfo($dest) {
	global $active_modules;

	if (substr(trim($dest),0,14) == 'app-languages,') {
		$exten = explode(',',$dest);
		$exten = $exten[1];
		$thisexten = languages_get($exten);
		if (empty($thisexten)) {
			return array();
		} else {
			$type = isset($active_modules['languages']['type'])?$active_modules['languages']['type']:'setup';
			return array('description' => 'Language : '.$thisexten['description'],
			             'edit_url' => 'config.php?display=languages&type='.$type.'&extdisplay='.urlencode($exten),
								  );
		}
	} else {
		return false;
	}
}

function languages_get_config($engine) {
	global $ext;
	switch ($engine) {
		case 'asterisk':
			$ext->addInclude('from-internal-additional', 'app-languages');
			foreach (languages_list() as $row) {
					$ext->add('app-languages',$row['language_id'], '', new ext_noop('Changing Channel to language: '.$row['lang_code'].' ('.$row['description'].')'));
					$ext->add('app-languages',$row['language_id'], '', new ext_setlanguage($row['lang_code']));
					$ext->add('app-languages',$row['language_id'], '', new ext_goto($row['dest']));
			}
		break;
	}
}

function languages_hookGet_config($engine) {
	global $ext;
	global $version;
	switch($engine) {
		case "asterisk":
			$priority = 'report';
			if (version_compare($version, "1.4", "ge")) { 
				$ext->splice('macro-user-callerid', 's', $priority,new ext_execif('$["${DB(AMPUSER/${AMPUSER}/language)}" != ""]', 'Set', 'CHANNEL(language)=${DB(AMPUSER/${AMPUSER}/language)}'));
			} else {
				$ext->splice('macro-user-callerid', 's', $priority,new ext_execif('$["${DB(AMPUSER/${AMPUSER}/language)}" != ""]', 'Set', 'LANGUAGE()=${DB(AMPUSER/${AMPUSER}/language)}'));
			}
		break;
	}
}

/**  Get a list of all languages
 */
function languages_list() {
	global $db;
	$sql = "SELECT language_id, description, lang_code, dest FROM languages ORDER BY description ";
	$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
	if(DB::IsError($results)) {
		die_freepbx($results->getMessage()."<br><br>Error selecting from languages");	
	}
	return $results;
}

function languages_get($language_id) {
	global $db;
	$sql = "SELECT language_id, description, lang_code, dest FROM languages WHERE language_id = ".$db->escapeSimple($language_id);
	$row = $db->getRow($sql, DB_FETCHMODE_ASSOC);
	if(DB::IsError($row)) {
		die_freepbx($row->getMessage()."<br><br>Error selecting row from languages");	
	}
	
	return $row;
}

function languages_add($description, $lang_code, $dest) {
	global $db;
	$sql = "INSERT INTO languages (description, lang_code, dest) VALUES (".
		"'".$db->escapeSimple($description)."', ".
		"'".$db->escapeSimple($lang_code)."', ".
		"'".$db->escapeSimple($dest)."')";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}

function languages_delete($language_id) {
	global $db;
	$sql = "DELETE FROM languages WHERE language_id = ".$db->escapeSimple($language_id);
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}

function languages_edit($language_id, $description, $lang_code, $dest) { 
	global $db;
	$sql = "UPDATE languages SET ".
		"description = '".$db->escapeSimple($description)."', ".
		"lang_code = '".$db->escapeSimple($lang_code)."', ".
		"dest = '".$db->escapeSimple($dest)."' ".
		"WHERE language_id = ".$db->escapeSimple($language_id);
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}

function languages_configpageinit($pagename) {
	global $currentcomponent;

	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$extension = isset($_REQUEST['extension'])?$_REQUEST['extension']:null;
	$tech_hardware = isset($_REQUEST['tech_hardware'])?$_REQUEST['tech_hardware']:null;

	// We only want to hook 'users' or 'extensions' pages.
	if ($pagename != 'users' && $pagename != 'extensions') 
		return true;
	// On a 'new' user, 'tech_hardware' is set, and there's no extension. Hook into the page.
	if ($tech_hardware != null || $pagename == 'users') {
		language_applyhooks();
		$currentcomponent->addprocessfunc('languages_configprocess', 8);
	} elseif ($action=="add") {
		// We don't need to display anything on an 'add', but we do need to handle returned data.
		$currentcomponent->addprocessfunc('languages_configprocess', 8);
	} elseif ($extdisplay != '') {
		// We're now viewing an extension, so we need to display _and_ process.
		language_applyhooks();
		$currentcomponent->addprocessfunc('languages_configprocess', 8);
	}
}

function language_applyhooks() {
	global $currentcomponent;

	// Add the 'process' function - this gets called when the page is loaded, to hook into 
	// displaying stuff on the page.
	$currentcomponent->addguifunc('languages_configpageload');
}

// This is called before the page is actually displayed, so we can use addguielem().
function languages_configpageload() {
	global $currentcomponent;

	// Init vars from $_REQUEST[]
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	
	// Don't display this stuff it it's on a 'This xtn has been deleted' page.
	if ($action != 'del') {
		$langcode = languages_user_get($extdisplay);

		$section = _('Language');
		$msgInvalidLanguage = _('Please enter a valid Language Code');
		$currentcomponent->addguielem($section, new gui_textbox('langcode', $langcode, _('Language Code'), _('The language code for this user. This will result in messages such as voiclangcode prompts to use the selected language if installed.'), "!isFilename()", $msgInvalidLanguage, true));
	}
}

function languages_configprocess() {
	//create vars from the request
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$ext = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$extn = isset($_REQUEST['extension'])?$_REQUEST['extension']:null;
	$langcode = isset($_REQUEST['langcode'])?$_REQUEST['langcode']:null;

	if ($ext==='') { 
		$extdisplay = $extn; 
	} else {
		$extdisplay = $ext;
	} 
	if ($action == "add" || $action == "edit") {
		if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true) {
			languages_user_update($extdisplay, $langcode);
		}
	} elseif ($action == "del") {
		languages_user_del($extdisplay);
	}
}

function languages_user_get($xtn) {
	global $astman;

	// Retrieve the language configuraiton from this user from ASTDB
	$langcode = $astman->database_get("AMPUSER",$xtn."/language");

	return $langcode;
}

function languages_user_update($ext, $langcode) {
	global $astman;
	// Update the settings in ASTDB
	$astman->database_put("AMPUSER",$ext."/language",$langcode);
}

function languages_user_del($ext) {
	global $astman;

	// Clean up the tree when the user is deleted
	$astman->database_deltree("AMPUSER/$ext/language");
}

function languages_check_destinations($dest=true) {
	global $active_modules;

	$destlist = array();
	if (is_array($dest) && empty($dest)) {
		return $destlist;
	}
	$sql = "SELECT language_id, dest, description FROM languages ";
	if ($dest !== true) {
		$sql .= "WHERE dest in ('".implode("','",$dest)."')";
	}
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	$type = isset($active_modules['languages']['type'])?$active_modules['languages']['type']:'setup';

	foreach ($results as $result) {
		$thisdest = $result['dest'];
		$thisid   = $result['language_id'];
		$destlist[] = array(
			'dest' => $thisdest,
			'description' => 'Language Change: '.$result['description'],
			'edit_url' => 'config.php?display=languages&type='.$type.'&extdisplay='.urlencode($thisid),
		);
	}
	return $destlist;
}
?>
