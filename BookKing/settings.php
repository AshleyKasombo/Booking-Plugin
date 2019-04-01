<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     mod_bookking
 * @category    admin
 * @copyright   2019 Shivaar Sooklal <shivaarsooklal.108@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    //require_once($CFG->dirroot.'/mod/bookking/lib.php');

    $settings->add(new admin_setting_configcheckbox('mod_bookking/allteachersgrading',
                     get_string('allteachersgrading', 'bookking'),
                     get_string('allteachersgrading_desc', 'bookking'),
                     0));

    $settings->add(new admin_setting_configcheckbox('mod_bookking/showemailplain',
                     get_string('showemailplain', 'bookking'),
                     get_string('showemailplain_desc', 'bookking'),
                     0));

    $settings->add(new admin_setting_configcheckbox('mod_bookking/groupscheduling',
                     get_string('groupscheduling', 'bookking'),
                     get_string('groupscheduling_desc', 'bookking'),
                     1));

    $settings->add(new admin_setting_configtext('mod_bookking/maxstudentlistsize',
                     get_string('maxstudentlistsize', 'bookking'),
                     get_string('maxstudentlistsize_desc', 'bookking'),
                     200, PARAM_INT));

}
