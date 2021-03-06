<?php

/**
 * Global configuration settings for the bookking module.
 *
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    require_once($CFG->dirroot.'/mod/bookking/lib.php');

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

    $settings->add(new admin_setting_configcheckbox('mod_bookking/mixindivgroup',
                     get_string('mixindivgroup', 'bookking'),
                     get_string('mixindivgroup_desc', 'bookking'),
                     1));

    $settings->add(new admin_setting_configtext('mod_bookking/maxstudentlistsize',
                     get_string('maxstudentlistsize', 'bookking'),
                     get_string('maxstudentlistsize_desc', 'bookking'),
                     200, PARAM_INT));

    $settings->add(new admin_setting_configtext('mod_bookking/uploadmaxfiles',
                     get_string('uploadmaxfilesglobal', 'bookking'),
                     get_string('uploadmaxfilesglobal_desc', 'bookking'),
                     5, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('mod_bookking/revealteachernotes',
                    get_string('revealteachernotes', 'bookking'),
                    get_string('revealteachernotes_desc', 'bookking'),
                    0));

}
