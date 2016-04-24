<?php

/**
 * Block simple_nav
 *
 * @package block_simple_nav
 * @copyright Georg Maißer, David Bogner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $name = 'block_simple_nav/displayoptions';
    $visiblename = get_string('displayoptions', 'block_simple_nav');
    $description = get_string('displayoptionsdesc', 'block_simple_nav');
    $defaultsetting = 1;
    $choices = array(1 => get_string('displaywithicons', 'block_simple_nav'), 
        2 => get_string('displaysingleicon', 'block_simple_nav'), 
        3 => get_string('displaytwoicons', 'block_simple_nav')
    );
    $setting = new admin_setting_configselect($name, $visiblename, $description, $defaultsetting, 
            $choices);
    $settings->add($setting);
}