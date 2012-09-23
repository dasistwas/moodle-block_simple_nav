<?php

	
    $string['pluginname'] = 'Simple navigation';
    $string['simple_nav'] = 'Simple navigation';
    $string['blockstring'] = 'Feld Titel';
    $string['sn_home'] = 'Home: Leave empty for using Moodle default';
    $string['sn_space'] = 'Intent Symbol';
    $string['sn_show_subcategories'] = 'Show Subcategories';
    $string['sn_show_courses'] = 'Show courses';
    $string['sn_show_modules'] = 'Show modules';
    $string['sn_modules_in_courses'] = 'Show modules in courses';
    $string['sn_modules_on_frontpage'] = 'Show modules on frontpage';
    
    // Get the all available modules and create the english strings
    global $CFG, $DB;
    $module_names = array();
    if (!$modules = $DB->get_records('modules', array(), 'name ASC')) {
        print_error('moduledoesnotexist', 'error');
    }
    	
    foreach ($modules as $module) {
    	$module_names[] = $module->name;
    }
    foreach ($module_names as $module_name) {
    	$modulename = 'sn_show_mods_'.$module_name;
    	$string[$modulename] = 'Show '.$module_name;
    }

    $string['simple_nav:viewcourse'] = 'View courses';
    