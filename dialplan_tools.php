<?php
/*
	Copyright (c) 2019-2023 Mark J Crane <markjcrane@fusionpbx.com>

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
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('dialplan_tool_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (!empty($_POST['dialplan_tools']) && is_array($_POST['dialplan_tools'])) {
		$action = $_POST['action'];
		$search = $_POST['search'] ?? null;
		$dialplan_tools = $_POST['dialplan_tools'];
	}

//process the http post data by action
	if (!empty($action) && is_array($dialplan_tools) && @sizeof($dialplan_tools) != 0) {

		//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: dialplan_tools.php');
			exit;
		}

		//prepare the array
		$x = 0;
		foreach ($dialplan_tools as $row) {
			$array['dialplan_tools'][$x]['checked'] = $row['checked'] ?? null;
			$array['dialplan_tools'][$x]['dialplan_tool_uuid'] = $row['dialplan_tool_uuid'];
			$array['dialplan_tools'][$x]['enabled'] = $row['enabled'] ?? null;
			$x++;
		}

		//prepare the database object
		$database = new database;
		$database->app_name = 'dialplan_tools';
		$database->app_uuid = 'dbe1a32f-4cf2-4986-af22-154ef66abfae';

		//send the array to the database class
		switch ($action) {
			case 'copy':
				if (permission_exists('dialplan_tool_add')) {
					$database->copy($array);
				}
				break;
			case 'toggle':
				if (permission_exists('dialplan_tool_edit')) {
					$database->toggle($array);
				}
				break;
			case 'delete':
				if (permission_exists('dialplan_tool_delete')) {
					$database->delete($array);
				}
				break;
		}

		//redirect the user
		header('Location: dialplan_tools.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"] ?? null;
	$order = $_GET["order"] ?? null;

//add the search
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//get the count
	$sql = "select count(dialplan_tool_uuid) ";
	$sql .= "from v_dialplan_tools ";
	if (permission_exists('dialplan_tool_all') && isset($_GET['show']) && $_GET['show'] == 'all') {
		$sql .= "where true ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (isset($search)) {
		$sql .= "and (";
		$sql .= "	lower(name) like :search ";
		$sql .= "	or lower(application) like :search ";
		$sql .= "	or lower(data) like :search ";
		$sql .= "	or lower(description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters ?? null, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = !empty($search) ? "&search=".$search : null;
	$param = !empty($_GET['show']) && $_GET['show'] == 'all' && permission_exists('dialplan_tool_all') ? "&show=all" : null;
	$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select ";
	$sql .= "domain_uuid, ";
	$sql .= "(select domain_name from v_domains where domain_uuid = d.domain_uuid) as domain_name, ";
	$sql .= "dialplan_tool_uuid, ";
	$sql .= "name, ";
	$sql .= "application, ";
	$sql .= "data, ";
	$sql .= "cast(enabled as text), ";
	$sql .= "description ";
	$sql .= "from v_dialplan_tools as d ";
	if (permission_exists('dialplan_tool_all') && isset($_GET['show']) && $_GET['show'] == 'all') {
		$sql .= "where true ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (isset($_GET["search"])) {
		$sql .= "and (";
		$sql .= "	lower(name) like :search ";
		$sql .= "	or lower(application) like :search ";
		$sql .= "	or lower(data) like :search ";
		$sql .= "	or lower(description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, 'name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$dialplan_tools = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-dialplan_tools'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dialplan_tools']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('dialplan_tool_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'dialplan_tool_edit.php']);
	}
	if (permission_exists('dialplan_tool_add') && $dialplan_tools) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('dialplan_tool_edit') && $dialplan_tools) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('dialplan_tool_delete') && $dialplan_tools) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('dialplan_tool_all')) {
		if (!empty($_GET['show']) && $_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search ?? null)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>(!empty($search) ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'dialplan_tools.php','style'=>(!empty($search) ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('dialplan_tool_add') && $dialplan_tools) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('dialplan_tool_edit') && $dialplan_tools) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('dialplan_tool_delete') && $dialplan_tools) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-dialplan_tools']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search ?? null)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('dialplan_tool_add') || permission_exists('dialplan_tool_edit') || permission_exists('dialplan_tool_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(empty($dialplan_tools) ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
	}
	if (!empty($_GET['show']) && $_GET['show'] == 'all' && permission_exists('dialplan_tool_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	}
	echo th_order_by('name', $text['label-name'], $order_by, $order);
	echo th_order_by('application', $text['label-application'], $order_by, $order);
	echo th_order_by('data', $text['label-data'], $order_by, $order);
	echo th_order_by('enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('description', $text['label-description'], $order_by, $order);
	if (permission_exists('dialplan_tool_edit') && !empty($_SESSION['theme']['list_row_edit_button']['boolean']) && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($dialplan_tools) && @sizeof($dialplan_tools) != 0) {
		$x = 0;
		foreach ($dialplan_tools as $row) {
			if (permission_exists('dialplan_tool_edit')) {
				$list_row_url = "dialplan_tool_edit.php?id=".urlencode($row['dialplan_tool_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('dialplan_tool_add') || permission_exists('dialplan_tool_edit') || permission_exists('dialplan_tool_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='dialplan_tools[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='dialplan_tools[$x][dialplan_tool_uuid]' value='".escape($row['dialplan_tool_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if (!empty($_GET['show']) && $_GET['show'] == 'all' && permission_exists('dialplan_tool_all')) {
				echo "	<td>".(isset($row['domain_name']) ? escape($row['domain_name']) : $text['label-global'])."</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('dialplan_tool_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['name'])."</a>\n";
			}
			else {
				echo "	".escape($row['name']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['application'])."</td>\n";
			echo "	<td>".escape($row['data'])."</td>\n";
			if (permission_exists('dialplan_tool_edit')) {
				echo "	<td class='no-link center'>\n";
				echo "		<input type='hidden' name='dialplan_tools[$x][enabled]' value='".escape($row['enabled'] ?? 'false')."' />\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['enabled'] ?? 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['description'])."</td>\n";
			if (permission_exists('dialplan_tool_edit') && !empty($_SESSION['theme']['list_row_edit_button']['boolean']) && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($dialplan_tools);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
