<?php
 
/**

 * Used to define settings of the block simple_nav
 * 

 */
class block_simple_nav_edit_form extends block_edit_form {
 
    protected function specific_definition($mform) {
 		global $CFG, $DB;
 		$module_names = array();
 		
		// get a list of all the modules names to print the necessary checkboxes 		
 		if (!$modules = $DB->get_records('modules', array(), 'name ASC')) {
        	print_error('moduledoesnotexist', 'error');
    	}
    	
    	foreach ($modules as $module) {
    		$module_names[] = $module->name;
    	}
 		
 		
 		//to include more module types in the settings, just enlarge this array
 		//$module_names = array('page','forum','choice', 'booking');
 		
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
        // A sample string variable with a default value.
        $mform->addElement('text', 'config_sn_home', get_string('sn_home', 'block_simple_nav'));
        $mform->setDefault('config_sn_home', '');
        $mform->setType('config_sn_home', PARAM_MULTILANG);
                
        //$mform->addElement('advcheckbox','config_show_subcategories', get_string('sn_show_subcategories', 'block_simple_nav'),null, array('group' => 2));
        //$mform->setDefault('config_show_subcategories', 1);
        //$mform->setType('config_show_subcategories', PARAM_MULTILANG); 
        
        $mform->addElement('advcheckbox','config_show_courses', get_string('sn_show_courses', 'block_simple_nav'),null, array('group' => 2));
        $mform->setDefault('config_show_courses', 1);
        $mform->setType('config_show_courses', PARAM_MULTILANG);
              
        $mform->addElement('advcheckbox','config_show_modules', get_string('sn_show_modules', 'block_simple_nav'),null, array('group' => 2));
        $mform->setDefault('config_show_modules', 1);
        $mform->setType('config_show_modules', PARAM_MULTILANG); 
                 
        $mform->addElement('html','<div style="font-weight: bold;">'.get_string('sn_modules_in_courses','block_simple_nav').'</div>');
                
        $this->add_checkbox_controller('group1');
        foreach ($module_names as $module_name) {
        	$mform->addElement('advcheckbox','config_show_mods_'.$module_name.'', get_string('sn_show_mods_'.$module_name.'', 'block_simple_nav'), null,array('group' => 'group1'));
        	
        	// Label and url are not real modules, so we don't want to show them by default.
        	if ($module_name == "label" || $module_name == "url") {
        		$mform->setDefault('config_show_mods_'.$module_name.'', 0);
        	} else {
        		$mform->setDefault('config_show_mods_'.$module_name.'', 1);
        	}
        	
        	$mform->setType('config_show_mods_'.$module_name.'', PARAM_MULTILANG);
        	$mform->disabledIf('config_show_mods_'.$module_name.'', 'config_show_modules', $condition = 'notchecked');
 		}

        $mform->addElement('html','<div>');        
        $mform->addElement('html','<div style="font-weight: bold;">'.get_string('sn_modules_on_frontpage','block_simple_nav').'</div>');
        $this->add_checkbox_controller('group2');
        foreach ($module_names as $module_name) {

        	$mform->addElement('advcheckbox','config_show_mods_frontpage_'.$module_name.'', get_string('sn_show_mods_'.$module_name.'', 'block_simple_nav'), null,array('group' => 'group2'));
			
			// Label and url are not real modules, so we don't want to show them by default.
			if ($module_name == "label" || $module_name == "url") {
        		$mform->setDefault('config_show_mods_frontpage_'.$module_name.'', 0);
			} else {
				$mform->setDefault('config_show_mods_frontpage_'.$module_name.'', 1);
			}
        	$mform->setType('config_show_mods_frontpage_'.$module_name.'', PARAM_MULTILANG);

        	$mform->disabledIf('config_show_mods_frontpage_'.$module_name.'', 'config_show_modules', $condition = 'notchecked');

        }

        $mform->addElement('html','</div>');

        
    }
}
