<?php

//
//
// This software is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This Moodle block is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @since 2.0
 * @package blocks
 * @copyright 2012 Georg Maißer und David Bogner http://www.edulabs.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The simple navigation tree block class
 *
 * Used to produce a simple navigation block
 *
 * @package blocks
 * @copyright 2012 Geord Maißer und David Bogner http://www.edulabs.org
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class block_simple_nav extends block_base {

	/** @var int */
	public static $navcount;
	/** @var string */
	public $blockname = null;
	/** @var bool */
	protected $contentgenerated = false;
	/** @var bool|null */
	protected $docked = null;

	function init() {
		global $CFG;
		$this->blockname = get_class($this);
		$this->title = get_string('pluginname', 'block_simple_nav');
		
	}

	/**
	 * All multiple instances of this block
	 * @return bool Returns true
	 */
	function instance_allow_multiple() {
		return true;
	}

	/**
	 * Set the applicable formats for this block to all
	 * @return array
	 */
	function applicable_formats() {
		return array('all' => true);
	}
	
	function specialization() {
		$this->title = isset($this->config->sn_blocktitle) ? format_string($this->config->sn_blocktitle) : format_string(get_string('pluginname', 'block_simple_nav'));
		if($this->title == ''){
			$this->title = format_string(get_string('pluginname', 'block_simple_nav'));
		}
	}
	/**
	 * Allow the user to configure a block instance
	 * @return bool Returns true
	 */
	function instance_allow_config() {
		return true;
	}

	/**
	 * The navigation block cannot be hidden by default as it is integral to
	 * the navigation of Moodle.
	 *
	 * @return false
	 */
	function  instance_can_be_hidden() {
		return true;
	}

	function instance_can_be_docked() {
		
		return (parent::instance_can_be_docked() && (empty($this->config->enabledock) || $this->config->enabledock=='yes'));
	}

	function get_required_javascript() {
		global $CFG;
		user_preference_allow_ajax_update('docked_block_instance_'.$this->instance->id, PARAM_INT);
		$this->page->requires->js_module('core_dock');
		$limit = 20;
		if (!empty($CFG->navcourselimit)) {
			$limit = $CFG->navcourselimit;
		}
		$expansionlimit = 0;
		if (!empty($this->config->expansionlimit)) {
			$expansionlimit = $this->config->expansionlimit;
		}
		$arguments = array(
				'id'             => $this->instance->id,
				'instance'       => $this->instance->id,
				'candock'        => $this->instance_can_be_docked(),
				'courselimit'    => $limit,
				'expansionlimit' => $expansionlimit
		);
		$this->page->requires->string_for_js('viewallcourses', 'moodle');
		$this->page->requires->yui_module(array('core_dock', 'moodle-block_navigation-navigation'), 'M.block_navigation.init_add_tree', array($arguments));
	}

	function simple_nav_collect_items ($myclass, $myid, $myname, $mydepth, $mytype, $mypath, $myicon, $myvisibility) {
		$item = array('myclass'=>$myclass, 'myid'=>$myid, 'myname'=>$myname, 'mydepth'=>$mydepth, 'mytype'=>$mytype, 'mypath'=>$mypath, 'myicon'=>$myicon, 'myvisibility'=>$myvisibility);

		return $item;
	}


	/**

	 * Looks at the navigation items and checks if

	 * the actual item is active

	 *

	 * @return  returns string (class) active_tree_node if acitv otherwise null

	 */
	function simple_nav_get_class_if_active ($myid, $mytype) {
		global $CFG, $PAGE;
		$myclass = null;

		if ($mytype == null && ($PAGE->pagetype <> 'site-index' && $PAGE->pagetype <>'admin-index')) {
			return $myclass;
		}
		elseif ($mytype == null && ($PAGE->pagetype == 'site-index' || $PAGE->pagetype =='admin-index')) {
			$myclass = ' active_tree_node';
			return $myclass;
		}
		elseif (!$mytype == null && ($PAGE->pagetype == 'site-index' || $PAGE->pagetype =='admin-index')) {
			return $myclass;
		}
		else {
			if(!empty($this->page->cm->id)){

				$modid = $this->page->cm->id;

			} else {

				$modid = false;

			}
			if ($mytype == 'module' && substr($PAGE->pagetype,0,3) == 'mod' && $myid == $modid) {
				$myclass = ' active_tree_node';
				
				return $myclass;
			}
			elseif ($mytype == 'course' && $myid== $this->page->course->id) {
				$myclass = ' active_tree_node';

				return $myclass;
			}
			elseif (isset($this->page->category->path)) {
				$mypath = explode('/',$this->page->category->path);
				if ($mytype == 'category' && ($myid== $this->page->category->id || in_array($myid,$mypath))) {
					$myclass = ' active_tree_node';
					return $myclass;
				}
			}
			else {
				return null;
			}
		}
	}


	function get_content() {

		global $CFG, $USER, $DB, $OUTPUT, $PAGE;
		$myopentag = '';
		$startcategories = array();
		$categories = array();

		if($this->content !== NULL) {
			return $this->content;
		}
		

		// We fetch a list of all the module names
		$module_names = array();
			
		// getting a list of all the modules names is inactive, as it leads to some problems with non standard modules. We do this manually beneath
		if (!$modules = $DB->get_records('modules', array(), 'name ASC')) {
			print_error('moduledoesnotexist', 'error');
		}

		//get all the Categories
		$categories = get_categories($parent = 'none', $sort = 'sortorder ASC', $shallow = false);
		
		
		
		// and make an array with all the names
		foreach ($modules as $module) {
		
			// we use from hereon "$module['name']. If we automatically fetch the list of modules (see above), this has to be changed to §module->name
			$show_mods = 'show_mods_'.$module->name;
			
			if (! empty($this->config)) {
				// we check here if there is a valid property available in the config-file for a specific module
				if (isset($this->config->$show_mods)) {
					$mods_value = $this->config->$show_mods;
				}
				// if this is not the case, we just turn it off
				else {
					$mods_value = 0;
				}
			}
			// this code is only needed when we want to exclude some of the modules
			 elseif ($module->name == "label" || $module->name == "url") {
				$mods_value = 0;
			}
			else {
				$mods_value = 1;
				
			}

			$module_item = array('name'=>$module->name, 'value'=>$mods_value);
			$module_items[] = $module_item;
		}

		foreach ($modules as $module) {
			$show_mods_frontpage = 'show_mods_frontpage_'.$module->name;
			
			if (! empty($this->config)) {
				// we check here if there is a valid property available in the config-file for a specific module
				if (isset($this->config->$show_mods_frontpage)) {
					$mods_value_frontpage = $this->config->$show_mods_frontpage;
				}
				// if this is not the case, we just turn it off
				else {
					//print_object($module);
					$mods_value_frontapge = 0;
					// echo "Please click on safe in the config section of this block, there is a module for which there is no config property defined";
				}
			}
			elseif ($module->name == "label" || $module->name == "url") {
				$mods_value_frontpage = 0;
			}
			else {
				$mods_value_frontpage = 1;
			}

			$module_frontpage_item = array('name'=>$module->name, 'value'=>$mods_value_frontpage);
			$module_frontpage_items[] = $module_frontpage_item;
		}



		// Get all the variables from the edit_form.php
		if (! empty($this->config->sn_home)) {
			$sn_home = $this->config->sn_home;
		} else {
			$sn_home = get_string('home');
		}

		if (! empty($this->config)) {
			$show_courses = $this->config->show_courses;
		}
		else {
			$show_courses = 1;

		}
		if (! empty($this->config)) {
			$show_modules = $this->config->show_modules;
		}
		else {
			$show_modules = 1;

		}
		if (! empty($this->config) && isset($this->config->show_toplevelnode)) {
			$show_toplevelnode = $this->config->show_toplevelnode;
		}
		else {
			$show_toplevelnode = 1;

		}
		

		$this->content = new stdClass;
		$this->content->items = array();
		$this->content->icons = array();
		$this->content->footer = '';
		
		//some variables
		$content = array();
		$items = array();
		$active_module_course = null;
		$is_active = false;
		$mybranch = array();
		$icon ='';
		$topnodelevel = 0;
		
		
		
		//get all the Courses
		$courses = get_courses($categoryid = 'all', $sort = 'c.sortorder ASC', $fields = 'c.*');
		//$courses = get_courses($categoryid = 'all', $sort = 'c.sortorder ASC', $fields = 'c.id, c.category, c.shortname, c.modinfo, c.visible');

		//get the Home-Node
		// first check if the Home Node is activated in the admin section
		$check_startcategory ="startcategory_home";
		if (isset($this->config->$check_startcategory)) {
			$startcategory_value = $this->config->$check_startcategory;
		}
		// if this is not set, we assume that we want to display everything
		else {
			$startcategory_value = 1;
		}
		if ($startcategory_value == 1) {
			$myclass = $this->simple_nav_get_class_if_active(null, null);
			$items[]=$this->simple_nav_collect_items($myclass, null, $sn_home, null, 'home', 0, null, null);
		}
		else {
			// if we don't use "home" we still print it, but don't use text. This is important to not destroy the html and javaScript
			$myclass = $this->simple_nav_get_class_if_active(null, null);
			$items[]=$this->simple_nav_collect_items($myclass, null, $sn_home, null, 'nohome', 0, null, null);
		}

		// here we set a value to determine, wether this is the topnode category
		
		// Now we run through all the categories
		foreach ($categories as $category) {
			
			
			// we just want to show the category if it is selected in the admin-section
			
			$check_startcategory = "startcategory_".$category->id;
			if (isset($this->config->$check_startcategory)) {
					$startcategory_value = $this->config->$check_startcategory;
			}
			// We look if there is no config object at all. If there is a config object, we put the value on 0
			elseif(!empty($this->config)) {
				$startcategory_value = 0;
				
			}
			// if there is no config object at all, we just show everything.
			else {
				$startcategory_value = 1;
			}
			
			//)
			//
			
			if ($startcategory_value >= 1) {
			
				//the myclass variable holds relevant CSS code for active nodes
				$myclass = $this->simple_nav_get_class_if_active($category->id, 'category');
				
				// look if we want to show the topnode. Else we set the name of the topnode category to ""
				if ($show_toplevelnode <> 1 && (empty($topnodelevel) || $topnodelevel == $category->depth)) {
					$category_name = "";
					$myclass.=" startingpoint";
					$topnodelevel = $category->depth;
				}
				else {
					$category_name = $category->name;
				}	
				
				// we don't write directly to $content[], because we have to change CSS-Code for active branches. So here we only build the navigation
				$items[]=$this->simple_nav_collect_items($myclass, $category->id, $category_name, $category->depth+1, 'category', $category->id, $icon, 1);
			}
			else {
				continue;
			}	
			if (substr($myclass, 0, 17) == ' active_tree_node') {
				$active_category_id = $category->id;
				$is_active = true;
			}
			
			
			foreach ($courses as $course) {

				if ($category->id == $course->category && $show_courses ) {

					$myclass = $this->simple_nav_get_class_if_active($course->id, 'course');
					$items[]=$this->simple_nav_collect_items ($myclass, $course->id, $course->shortname, $category->depth+2, 'course', $category->id, $icon, $course->visible);


					//don't show any modules if there is no access to the course
					if (!can_access_course($course)) {
						continue;
					}


					// this is to count back from the item to the whole active branch
					if ($myclass) {
						$is_active = true;
					}


					//Here we check the modules for each course
					//$modules = get_course_mods($course->id);
					$modules = get_fast_modinfo($course)->get_cms();
					
					if ($modules && !$show_modules) {
						continue;
					}


					//we run through them and add them to the $items
					foreach ($modules as $module) {						

						//this is necessary to be able to get the module name, we hereby fetch the module object
						$module_object = get_coursemodule_from_id($module->modname, $module->id);
						// show only modules that are visible to the user
						if (!$module->uservisible) {
							continue;
						}


						foreach ($module_items as $module_item) {
							if ($module_item['name'] == $module_object->modname && $module_item['value']) {
								$myclass = $this->simple_nav_get_class_if_active($module_object->id, 'module');
								$items[]=$this->simple_nav_collect_items ($myclass, $module_object->id, $module_object->name, $category->depth+3, 'module', $module_object->course, $module_object->modname, $course->visible);
								break;
							}
						}

					}



				}
			}
		}



		// the following lines are to get all the mods which are directly beneath the startpage. This is a special case, so we have to treat it differently.
		$modules = get_course_mods(1);
		if ($modules && $show_modules) {
			//we run through them and add them to the $items

			foreach ($modules as $module) {

				//this is necessary to be able to get the module name, we hereby fetch the module object
				$module_object = get_coursemodule_from_id($module->modname, $module->id);

				if (!$module_object->visible) {
					continue;
				}
				foreach ($module_frontpage_items as $module_item) {
					if ($module_item['name'] == $module_object->modname && $module_item['value']) {
						$myclass = $this->simple_nav_get_class_if_active($module_object->id, 'module');
						$items[]=$this->simple_nav_collect_items ($myclass, $module_object->id, $module_object->name, 1, 'module', $module_object->course, $module_object->modname, 1);
						break;
					}
				}
			}
		}


		$this->page->navigation->initialise();
		$navigation = clone($this->page->navigation);

		$renderer = $this->page->get_renderer('block_simple_nav');

		$this->content         =  new stdClass;
		$this->content->text   = $renderer->simple_nav_tree($items);
			


		// Set content generated to true so that we know it has been done
		$this->contentgenerated = true;

		return $this->content;
	}


}
