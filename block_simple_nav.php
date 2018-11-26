<?php
// This software is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This Moodle block is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * The simple navigation tree block class
 *
 * Used to produce a simple navigation block
 *
 * @package blocks
 * @copyright 2012-2016 Georg Maißer und David Bogner http://www.edulabs.org
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class block_simple_nav extends block_base {

    /**
     * @var string
     */
    public $blockname = null;

    /**
     * @var bool
     */
    protected $contentgenerated = false;

    /**
     * @var bool|null
     */
    protected $docked = null;

    /**
     * @var array
     */
    protected $categories = array();

    /**
     * @var array
     */
    protected $courses = array();

    public static function simple_nav_get_courses() {
        static $mycourses;
        if (empty($mycourses)) {
            $mycourses = get_courses($categoryid = 'all', $sort = 'c.sortorder ASC',
                    $fields = 'c.id, c.category, c.shortname, c.visible');
        }
        return $mycourses;
    }

    function init() {
        global $CFG;
        $this->blockname = get_class($this);
        $this->title = get_string('pluginname', 'block_simple_nav');
        $this->courses = self::simple_nav_get_courses();
    }

    /**
     * All multiple instances of this block
     *
     * @return bool Returns true
     */
    function instance_allow_multiple() {
        return true;
    }

    /**
     * Set the applicable formats for this block to all
     *
     * @return array
     */
    function applicable_formats() {
        return array('all' => true
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see block_base::specialization()
     */
    function specialization() {
        $this->title = isset($this->config->sn_blocktitle) ? format_string(
                $this->config->sn_blocktitle) : format_string(
                get_string('pluginname', 'block_simple_nav'));
        if ($this->title == '') {
            $this->title = format_string(get_string('pluginname', 'block_simple_nav'));
        }
    }

    /**
     * Allow the user to configure a block instance
     *
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
    function instance_can_be_hidden() {
        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @see block_base::instance_can_be_docked()
     */
    function instance_can_be_docked() {
        return (parent::instance_can_be_docked() &&
                 (empty($this->config->enabledock) || $this->config->enabledock == 'yes'));
    }

    /**
     * (non-PHPdoc)
     *
     * @see block_base::get_required_javascript()
     */
    function get_required_javascript() {
        parent::get_required_javascript();
        $arguments = array(
            'instanceid' => $this->instance->id
        );
        $this->page->requires->string_for_js('viewallcourses', 'moodle');
        $this->page->requires->js_call_amd('block_simple_nav/navblock', 'init', $arguments);
    }

    /**
     * collect items for rendering
     *
     * @param string $myclass
     * @param int $myid
     * @param string $myname
     * @param int $mydepth
     * @param string $mytype
     * @param string $mypath
     * @param string $myicon
     * @param int $myvisibility
     * @return array
     */
    function simple_nav_collect_items($myclass, $myid, $myname, $mydepth, $mytype, $mypath, $myicon,
            $myvisibility) {
        $item = array('myclass' => $myclass, 'myid' => $myid, 'myname' => $myname,
            'mydepth' => $mydepth, 'mytype' => $mytype, 'mypath' => $mypath, 'myicon' => $myicon,
            'myvisibility' => $myvisibility
        );
        return $item;
    }

    /**
     * create $this->categories array with all cats in an array from given parent category id
     *
     * @param integer $parentcategoryid
     */
    public function simple_nav_get_categories($parentcategoryid) {
        $childcategories = coursecat::get($parentcategoryid)->get_children();
        if (!empty($childcategories)) {
            foreach ($childcategories as $catid => $childcategory) {
                $this->categories[$catid] = $childcategory;
                if ($childcategory->get_children_count() > 0) {
                    self::simple_nav_get_categories($catid);
                }
            }
        }
    }

    /**
     * Looks at the navigation items and checks if
     * the actual item is active
     *
     * @return returns string (class) active_tree_node if active otherwise null
     */
    protected function simple_nav_get_class_if_active($myid, $mytype) {
        global $PAGE;
        $myclass = null;

        if ($mytype == null && ($PAGE->pagetype != 'site-index' && $PAGE->pagetype != 'admin-index')) {
            return $myclass;
        } elseif ($mytype == null &&
                 ($PAGE->pagetype == 'site-index' || $PAGE->pagetype == 'admin-index')) {
            $myclass = ' active_tree_node';
            return $myclass;
        } elseif (!$mytype == null &&
                 ($PAGE->pagetype == 'site-index' || $PAGE->pagetype == 'admin-index')) {
            return $myclass;
        } else {
            if (!empty($this->page->cm->id)) {

                $modid = $this->page->cm->id;
            } else {

                $modid = false;
            }
            if ($mytype == 'module' && substr($PAGE->pagetype, 0, 3) == 'mod' && $myid == $modid) {
                $myclass = ' active_tree_node';

                return $myclass;
            } elseif ($mytype == 'course' && $myid == $this->page->course->id) {
                $myclass = ' active_tree_node';

                return $myclass;
            } elseif (isset($this->page->category->path)) {
                $mypath = explode('/', $this->page->category->path);
                if ($mytype == 'category' &&
                         ($myid == $this->page->category->id || in_array($myid, $mypath))) {
                    $myclass = ' active_tree_node';
                    return $myclass;
                }
            } else {
                return null;
            }
        }
    }

    /**
     * Prepare the navigation depending on the settings
     * (non-PHPdoc)
     *
     * @see block_base::get_content()
     */
    public function get_content() {
        global $CFG, $DB;
        require_once ($CFG->libdir . '/coursecatlib.php');
        $module_frontpage_items = array();
        $module_items = array();
        if ($this->content !== NULL) {
            return $this->content;
        }

        // getting a list of all the modules names is inactive, as it leads to some problems with
        // non standard modules. We do this manually beneath
        if (!$allmodules = $DB->get_records('modules', array(), 'name ASC', 'name')) {
            print_error('moduledoesnotexist', 'error');
        }

        // get all the categories and courses from the navigation node
        // save them in $this->categories
        if (empty($this->categories)) {
            $this->simple_nav_get_categories(0); // coursecat::get(0)->get_children();//
                                                 // get_course_category_tree();
        }

        // and make an array with all the names
        foreach ($allmodules as $module) {

            // we use from hereon "$module['name']. If we automatically fetch the list of modules
            // (see above), this has to be changed to §module->name
            $show_mods = 'show_mods_' . $module->name;

            if (!empty($this->config)) {
                // we check here if there is a valid property available in the config-file for a
                // specific module
                if (isset($this->config->$show_mods)) {
                    $mods_value = $this->config->$show_mods;
                }                 // if this is not the case, we just turn it off
                else {
                    $mods_value = 0;
                }
            }            // this code is only needed when we want to exclude some of the modules
            elseif ($module->name == "label" || $module->name == "url") {
                $mods_value = 0;
            } else {
                $mods_value = 1;
            }

            if ($mods_value == 1) {
                $module_items[] = $module->name;
            }
        }

        foreach ($allmodules as $module) {
            $show_mods_frontpage = 'show_mods_frontpage_' . $module->name;
            $mods_value_frontpage = null;

            if (!empty($this->config)) {
                // we check here if there is a valid property available in the config-file for a
                // specific module
                if (isset($this->config->$show_mods_frontpage)) {
                    $mods_value_frontpage = $this->config->$show_mods_frontpage;
                }                 // if this is not the case, we just turn it off
                else {
                    $mods_value_frontapge = 0;
                }
            } elseif ($module->name == "label" || $module->name == "url") {
                $mods_value_frontpage = 0;
            } else {
                $mods_value_frontpage = 1;
            }

            if ($mods_value_frontpage == 1) {
                $module_frontpage_items[] = $module->name;
            }
        }

        // Get all the variables from the edit_form.php
        if (!empty($this->config->sn_home)) {
            $sn_home = $this->config->sn_home;
        } else {
            $sn_home = get_string('home');
        }

        if (!empty($this->config)) {
            $show_courses = $this->config->show_courses;
        } else {
            $show_courses = 1;
        }
        if (!empty($this->config)) {
            $show_modules = $this->config->show_modules;
        } else {
            $show_modules = 1;
        }
        if (!empty($this->config) && isset($this->config->show_toplevelnode)) {
            $show_toplevelnode = $this->config->show_toplevelnode;
        } else {
            $show_toplevelnode = 1;
        }

        // some variables
        $items = array();
        $is_active = false;
        $icon = '';
        $topnodelevel = 0;

        // get the Home-Node
        // first check if the Home Node is activated in the admin section
        $check_startcategory = "startcategory_home";
        if (isset($this->config->$check_startcategory)) {
            $startcategory_value = $this->config->$check_startcategory;
        }         // if this is not set, we assume that we want to display everything
        else {
            $startcategory_value = 1;
        }
        if ($startcategory_value == 1) {
            $myclass = $this->simple_nav_get_class_if_active(null, null);
            $items[] = $this->simple_nav_collect_items($myclass, null, $sn_home, null, 'home', 0,
                    null, null);
        } else {
            // if we don't use "home" we still print it, but don't use text. This is important to
            // not destroy the html and javaScript
            $myclass = $this->simple_nav_get_class_if_active(null, null);
            $items[] = $this->simple_nav_collect_items($myclass, null, $sn_home, null, 'nohome', 0,
                    null, null);
        }

        // Now we run through all the categories
        foreach ($this->categories as $catid => $category) {
            // we just want to show the category if it is selected in the admin-section

            $check_startcategory = "startcategory_" . $catid;
            if (isset($this->config->$check_startcategory)) {
                $startcategory_value = $this->config->$check_startcategory;
            }            // We look if there is no config object at all. If there is a config object, we put the
            // value on 0
            elseif (!empty($this->config)) {
                $startcategory_value = 0;
            }             // if there is no config object at all, we just show everything.
            else {
                $startcategory_value = 1;
            }

            if ($startcategory_value >= 1) {

                // the myclass variable holds relevant CSS code for active nodes
                $myclass = $this->simple_nav_get_class_if_active($category->id, 'category');

                // look if we want to show the topnode. Else we set the name of the topnode category
                // to ""
                if ($show_toplevelnode != 1 &&
                         (empty($topnodelevel) || $topnodelevel == $category->depth)) {
                    $category_name = "";
                    $myclass .= " startingpoint";
                    $topnodelevel = $category->depth;
                } else {
                    $category_name = $category->name;
                }

                // we don't write directly to $content[], because we have to change CSS-Code for
                // active branches. So here we only build the navigation
                $items[] = $this->simple_nav_collect_items($myclass, $category->id, $category_name,
                        $category->depth + 1, 'category', $category->id, $icon, 1);
            } else {
                continue;
            }
            if (substr($myclass, 0, 17) == ' active_tree_node') {
                $active_category_id = $category->id;
                $is_active = true;
            }

            foreach ($this->courses as $course) {

                if ($category->id == $course->category && $show_courses) {

                    $myclass = $this->simple_nav_get_class_if_active($course->id, 'course');
                    $items[] = $this->simple_nav_collect_items($myclass, $course->id,
                            $course->shortname, $category->depth + 2, 'course', $category->id, $icon,
                            $course->visible);

                    // don't show any modules if there is no access to the course
                    if (!can_access_course($course)) {
                        continue;
                    }

                    // this is to count back from the item to the whole active branch
                    if ($myclass) {
                        $is_active = true;
                    }
                    if (!$show_modules) {
                        continue;
                    }
                    // Here we check the modules for each course
                    $modules = get_fast_modinfo($course->id)->get_cms();

                    if (empty($modules)) {
                        continue;
                    }

                    // we run through them and add them to the $items
                    foreach ($modules as $module) {
                        // show only modules that are visible to the user
                        if (!$module->uservisible || !in_array($module->modname, $module_items)) {
                            continue;
                        }
                        $myclass = $this->simple_nav_get_class_if_active($module->id, 'module');
                        $items[] = $this->simple_nav_collect_items($myclass, $module->id,
                                $module->name, $category->depth + 3, 'module', $module->course,
                                $module->modname, $course->visible);
                    }
                }
            }
            // the following lines are to get all the mods which are directly beneath the startpage.
            // This is a special case, so we have to treat it differently.
            $modules = get_fast_modinfo(1)->get_cms();
            if ($modules && $show_modules) {
                // we run through them and add them to the $items

                foreach ($modules as $module) {
                    if (!$module->uservisible || !in_array($module->modname,
                            $module_frontpage_items)) {
                        continue;
                    }
                    $myclass = $this->simple_nav_get_class_if_active($module->id, 'module');
                    $items[] = $this->simple_nav_collect_items($myclass, $module->id, $module->name,
                            1, 'module', $module->course, $module->modname, 1);
                }
            }
        }

        $renderer = $this->page->get_renderer('block_simple_nav');
        $this->content = new stdClass();
        $this->content->text = $renderer->simple_nav_tree($items);

        // Set content generated to true so that we know it has been done
        $this->contentgenerated = true;

        return $this->content;
    }

    function has_config() {
        return true;
    }

    /**
     * Returns the role that best describes the simple_nav block... 'navigation'
     *
     * @return string 'navigation'
     */
    public function get_aria_role() {
        return 'navigation';
    }
}
