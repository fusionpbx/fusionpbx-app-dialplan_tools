<?php

	//application details
		$apps[$x]['name'] = 'Dialplan Tools';
		$apps[$x]['uuid'] = 'dbe1a32f-4cf2-4986-af22-154ef66abfae';
		$apps[$x]['category'] = 'Switch';
		$apps[$x]['subcategory'] = 'Dialplan';
		$apps[$x]['version'] = '1.3';
		$apps[$x]['license'] = 'Member';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en-us'] = '';

	//destination details
		$y=0;
		$apps[$x]['destinations'][$y]['type'] = "sql";
		$apps[$x]['destinations'][$y]['label'] = "dialplan_tools";
		$apps[$x]['destinations'][$y]['name'] = "dialplan_tools";
		$apps[$x]['destinations'][$y]['where'] = "where (domain_uuid = '\${domain_uuid}' or domain_uuid is null) and enabled = 'true' ";
		$apps[$x]['destinations'][$y]['order_by'] = "name asc";
		$apps[$x]['destinations'][$y]['field']['dialplan_tool_uuid'] = "dialplan_tool_uuid";
		$apps[$x]['destinations'][$y]['field']['name'] = "name";
		$apps[$x]['destinations'][$y]['field']['application'] = "application";
		$apps[$x]['destinations'][$y]['field']['data'] = "data";
		$apps[$x]['destinations'][$y]['select_value']['dialplan'] = "\${application}:\${data}";
		$apps[$x]['destinations'][$y]['select_value']['ivr'] = "menu-exec-app:\${application} \${data}";
		$apps[$x]['destinations'][$y]['select_label'] = "\${name}";

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = 'dialplan_tool_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'dialplan_tool_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'dialplan_tool_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'dialplan_tool_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'dialplan_tool_domain';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'dialplan_tool_all';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'dialplan_tool_destinations';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;

	//Dialplan Tools
		$y = 0;
		$apps[$x]['db'][$y]['table']['name'] = 'v_dialplan_tools';
		$apps[$x]['db'][$y]['table']['parent'] = '';
		$z = 0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'dialplan_tool_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_domains';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'domain_uuid';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search_by'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the name.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'application';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search_by'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the application';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'data';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search_by'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the data.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'enabled';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'boolean';
		$apps[$x]['db'][$y]['fields'][$z]['toggle'] = ['true','false'];
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Select to enable or disable.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search_by'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the description.';
		$z++;

?>
