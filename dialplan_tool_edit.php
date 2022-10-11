<?php
/*
	Copyright (c) 2019-2022 Mark J Crane <markjcrane@fusionpbx.com>
	
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions
	are met:
		1. Redistributions of source code must retain the above copyright
		notice, this list of conditions and the following disclaimer.
	
		2. Redistributions in binary form must reproduce the above copyright
		notice, this list of conditions and the following disclaimer in the
		documentation and/or other materials provided with the distribution.
	THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
	IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
	ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
	FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
	DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
	OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
	HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
	LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
	OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
	SUCH DAMAGE.
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('dialplan_tool_add') || permission_exists('dialplan_tool_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$dialplan_tool_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$name = $_POST["name"];
		$application = $_POST["application"];
		$data = $_POST["data"];
		$enabled = $_POST["enabled"];
		$description = $_POST["description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: dialplan_tools.php');
				exit;
			}

		//process the http post data by submitted action
			if ($_POST['action'] != '' && strlen($_POST['action']) > 0) {

				//prepare the array(s)
				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('dialplan_tool_add')) {
							$obj = new database;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('dialplan_tool_delete')) {
							$obj = new database;
							$obj->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('dialplan_tool_update')) {
							$obj = new database;
							$obj->toggle($array);
						}
						break;
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					header('Location: dialplan_tool_edit.php?id='.$id);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			if (strlen($name) == 0) { $msg .= $text['message-required']." ".$text['label-name']."<br>\n"; }
			if (strlen($application) == 0) { $msg .= $text['message-required']." ".$text['label-application']."<br>\n"; }
			//if (strlen($data) == 0) { $msg .= $text['message-required']." ".$text['label-data']."<br>\n"; }
			if (strlen($enabled) == 0) { $msg .= $text['message-required']." ".$text['label-enabled']."<br>\n"; }
			//if (strlen($description) == 0) { $msg .= $text['message-required']." ".$text['label-description']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//add the dialplan_tool_uuid
			if (!is_uuid($_POST["dialplan_tool_uuid"])) {
				$dialplan_tool_uuid = uuid();
			}

		//prepare the array
			$array['dialplan_tools'][0]['dialplan_tool_uuid'] = $dialplan_tool_uuid;
			if (permission_exists('dialplan_tool_domain')) {
				if (is_uuid($_POST["domain_uuid"])) {
					$array['dialplan_tools'][0]['domain_uuid'] = $_POST['domain_uuid'];
				}
				else {
					$array['dialplan_tools'][0]['domain_uuid'] = ''; //global
				}
			}
			else {
				$array['dialplan_tools'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			}
			$array['dialplan_tools'][0]['name'] = $name;
			if (!preg_match("/system/i", $application)) { 
				$array['dialplan_tools'][0]['application'] = $application;
			}
			if (!preg_match("/system/i", $data)) { 
				$array['dialplan_tools'][0]['data'] = $data;
			}
			$array['dialplan_tools'][0]['enabled'] = $enabled;
			$array['dialplan_tools'][0]['description'] = $description;

		//save the data
			$database = new database;
			$database->app_name = 'dialplan tools';
			$database->app_uuid = 'dbe1a32f-4cf2-4986-af22-154ef66abfae';
			$database->save($array);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				//header('Location: dialplan_tools.php');
				header('Location: dialplan_tool_edit.php?id='.urlencode($dialplan_tool_uuid));
				return;
			}
	}

//get the list of applications
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		$result = event_socket_request($fp, 'api show application as json');
		if (is_array($result)) {
			$result = $result['Content'];
		}
		$array = json_decode($result, true);
		$dialplan_tools = $array['rows'];
		unset($result, $fp);
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sql = "select ";
		$sql .= "domain_uuid, ";
		$sql .= "dialplan_tool_uuid, ";
		$sql .= "name, ";
		$sql .= "application, ";
		$sql .= "data, ";
		$sql .= "cast(enabled as text), ";
		$sql .= "description ";
		$sql .= "from v_dialplan_tools ";
		$sql .= "where dialplan_tool_uuid = :dialplan_tool_uuid ";
		//$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['dialplan_tool_uuid'] = $dialplan_tool_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$domain_uuid = $row["domain_uuid"];
			$name = $row["name"];
			$application = $row["application"];
			$data = $row["data"];
			$enabled = $row["enabled"];
			$description = $row["description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-dialplan_tool'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='dialplan_tool_uuid' value='".escape($dialplan_tool_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dialplan_tool']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'dialplan_tools.php']);
	if ($action == 'update') {
		if (permission_exists('_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-dialplan_tools']."\n";
	echo "<br /><br />\n";

	if ($action == 'update') {
		if (permission_exists('dialplan_tool_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('dialplan_tool_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='name' maxlength='255' value='".escape($name)."'>\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-application']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='application' class='formfld' style='width: auto; ".$element['visibility']."' onchange='change_to_input(this);'>\n";
	if (strlen($application) > 0) {
		echo "		<option value=\"".escape($application)."\" selected=\"selected\">".escape($application)."</option>\n";
	}
	else {
		echo "		<option value=''></option>\n";
	}
	if (is_array($dialplan_tools)) {
		foreach ($dialplan_tools as $row) {
			if ($row['name'] != "name" && $row['name'] != "system") {
		 		echo "	<option value='".escape($row['name'])."'>".escape($row['name'])."</option>\n";
			}
		}
	}
	echo "	</select>\n";
	echo " <br />\n";
	echo " ".$text['description-application']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-data']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='data' maxlength='255' value='".escape($data)."'>\n";
	echo "<br />\n";
	echo $text['description-data']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('dialplan_tool_domain')) {
		echo "	<tr>\n";
		echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "		".$text['label-domain']."\n";
		echo "	</td>\n";
		echo "	<td class='vtable' align='left'>\n";
		echo "		<select class='formfld' name='domain_uuid'>\n";
		if (!is_uuid($domain_uuid)) {
			echo "		<option value='' selected='selected'>".$text['label-global']."</option>\n";
		}
		else {
			echo "		<option value=''>".$text['label-global']."</option>\n";
		}
		if (is_array($_SESSION['domains']) && @sizeof($_SESSION['domains']) != 0) {
			foreach ($_SESSION['domains'] as $row) {
				if ($row['domain_uuid'] == $domain_uuid) {
					echo "		<option value='".escape($row['domain_uuid'])."' selected='selected'>".escape($row['domain_name'])."</option>\n";
				}
				else {
					echo "		<option value='".escape($row['domain_uuid'])."'>".escape($row['domain_name'])."</option>\n";
				}
			}
		}
		echo "		</select>\n";
		echo "		<br />\n";
		//echo "		".$text['description-domain_name']."\n";
		echo "	</td>\n";
		echo "	</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='enabled'>\n";
	if ($enabled == "true") {
		echo "		<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "		<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($enabled == "false") {
		echo "		<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "		<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='description' maxlength='255' value='".escape($description)."'>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
