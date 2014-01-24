<?php
 
/**

 * Used to define settings of the block simple_nav
 * 

 */
class block_simple_nav_edit_form extends block_edit_form {
	
	/** @var array */
	protected $categories = array();
	
	/**
	 * create $this->categories array with all cats in an array from given parent category id
	 *
	 * @param integer $parentcategoryid
	 */
	protected function simple_nav_get_categories($parentcategoryid){
		$childcategories = coursecat::get($parentcategoryid)->get_children();
		if(!empty($childcategories)){
			foreach ($childcategories as $catid => $childcategory){
				$this->categories[$catid] = $childcategory;
				if($childcategory->get_children_count() > 0){
					self::simple_nav_get_categories($catid);
				}
			}
		}
	}
    
    protected function specific_definition($mform) {
 		global $CFG, $DB;
 		require_once($CFG->libdir . '/coursecatlib.php');
 		$module_names = array();
 		
 		// get a list of all the categories
 		
 		//get all the Categories
		// get_course_category_tree();
		$this->simple_nav_get_categories(0);
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
        // A sample string variable with a default value.
        $mform->addElement('text', 'config_sn_home', get_string('sn_home', 'block_simple_nav'));
        $mform->setDefault('config_sn_home', '');
        $mform->setType('config_sn_home', PARAM_MULTILANG);
        
        $mform->addElement('text', 'config_sn_blocktitle', get_string('sn_blocktitle', 'block_simple_nav'));
        $mform->setDefault('config_sn_blocktitle', '');
        $mform->setType('config_sn_blocktitle', PARAM_MULTILANG);
        
        $mform->addElement('advcheckbox','config_show_courses', get_string('sn_show_courses', 'block_simple_nav'),null, array('group' => 2));
        $mform->setDefault('config_show_courses', 1);
        $mform->setType('config_show_courses', PARAM_MULTILANG);
              
        $mform->addElement('advcheckbox','config_show_modules', get_string('sn_show_modules', 'block_simple_nav'),null, array('group' => 2));
        $mform->setDefault('config_show_modules', 1);
        $mform->setType('config_show_modules', PARAM_MULTILANG);
        
        // show link toplevelnode
        $mform->addElement('advcheckbox','config_show_toplevelnode', get_string('sn_show_toplevelnode', 'block_simple_nav'),null, array('group' => 2));
        $mform->setDefault('config_show_toplevelnode', 1);
        $mform->setType('config_show_toplevelnode', PARAM_MULTILANG);
        
        
        //we want to be able to select the categories we want to include here
        
        $mform->addElement('html','<div style="font-weight: bold;">'.get_string('sn_startcategory','block_simple_nav').'</div>');
        
		//first we manually enter the home node
		$mform->addElement('advcheckbox','config_startcategory_home', "home", null,array('group' => 'group3'));
 		$mform->setDefault('config_startcategory_home', 1);
        $mform->setType('config_startcategory_home', PARAM_MULTILANG);
        $mform->disabledIf('config_startcategory_home', "home", $condition = 'checked');
 		foreach ($this->categories as $category) {
 			// change the value here if you want to control more of the subcategories etc. After changing the value, you have to check if everything is displayed as you want. It might be necessary to reinstall the whole block (especially when you decrease the value)
 			if ($category->depth<=6) {
 				// if you enter a value for str_repeat (like "--" i.e., the categories and subcategories will be easier to distinguish
 				$mform->addElement('advcheckbox','config_startcategory_'.$category->id.'', str_repeat("*",$category->depth).$category->name.'', null,array('group' => 'group3'));
 				$mform->setDefault('config_startcategory_'.$category->id.'', 0);
        		$mform->setType('config_startcategory_'.$category->id.'', PARAM_MULTILANG);
        		//$mform->setValue($category->depth);
        		$mform->disabledIf('config_startcategory_'.$category->id.'', $category->name, $condition = 'notchecked');
        	}
 		}
 		
 		// In this section we let the user chose which modules should be displayed
 		$mform->addElement('html','<div style="font-weight: bold;">'.get_string('sn_modules_in_courses','block_simple_nav').'</div>');
 		
		// get a list of all the modules names to print the necessary checkboxes 		
 		if (!$modules = $DB->get_records('modules', array(), 'name ASC')) {
        	print_error('moduledoesnotexist', 'error');
    	}
    	
    	foreach ($modules as $module) {
    		$module_names[] = $module->name;
    	}
        
        
                
        $this->add_checkbox_controller('group1');
        foreach ($module_names as $module_name) {
        	$mform->addElement('advcheckbox','config_show_mods_'.$module_name.'', get_string('pluginname',$module_name), null,array('group' => 'group1'));
        	
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

        	$mform->addElement('advcheckbox','config_show_mods_frontpage_'.$module_name.'', get_string('pluginname',$module_name), null,array('group' => 'group2'));
			
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
