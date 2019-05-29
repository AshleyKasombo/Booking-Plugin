<?php

/**
 * This file contains a renderer for the bookking module
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the bookking module.
 *
 */
class mod_bookking_renderer extends plugin_renderer_base {

    public function __construct($page = null, $target = null) {
        if ($page) {
            parent::__construct($page, $target);
        }
    }

    /**
     * Format a date in the current user's timezone.
     * @param int $date a timestamp
     * @return string printable date
     */
    public static function userdate($date) {
        if ($date == 0) {
            return '';
        } else {
            return userdate($date, get_string('strftimedaydate'));
        }
    }

    /**
     * Format a time in the current user's timezone.
     * @param int $date a timestamp
     * @return string printable time
     */
    public static function usertime($date) {
        if ($date == 0) {
            return '';
        } else {
            $timeformat = get_user_preferences('calendar_timeformat'); // Get user config.
            if (empty($timeformat)) {
                $timeformat = get_config(null, 'calendar_site_timeformat'); // Get calendar config if above not exist.
            }
            if (empty($timeformat)) {
                $timeformat = get_string('strftimetime'); // Get locale default format if both of the above do not exist.
            }
            return userdate($date, $timeformat);
        }
    }

    /**
     * Format a slot date and time, for use as a parameter in a language string.
     *
     * @param int $slotdate
     *            a timestamp, start time of the slot
     * @param int $duration
     *            length of the slot in minutes
     * @return stdClass date and time formatted for usage in language strings
     */
    public static function slotdatetime($slotdate, $duration) {
        $shortformat = get_string('strftimedatetimeshort');

        $a = new stdClass();
        $a->date = self::userdate($slotdate);
        $a->starttime = self::usertime($slotdate);
        $a->shortdatetime = userdate($slotdate, $shortformat);
        $a->endtime = self::usertime($slotdate + $duration * MINSECS);
        $a->duration = $duration;

        return $a;
    }

    /**
     * @var array a cached version of scale levels
     */
    protected $scalecache = array();

    /**
     * Get a list of levels in a grading scale.
     *
     * @param int $scaleid id number of the scale
     * @return array levels on the scale
     */
    public function get_scale_levels($scaleid) {
        global $DB;

        if (!array_key_exists($scaleid, $this->scalecache)) {
            $this->scalecache[$scaleid] = array();
            if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
                $levels = explode(',', $scale->scale);
                foreach ($levels as $levelid => $value) {
                    $this->scalecache[$scaleid][$levelid + 1] = $value;
                }
            }
        }
        return $this->scalecache[$scaleid];
    }

    /**
     * Formats a grade in a specific bookking for display.
     *
     * @param mixed $subject either a bookking instance or a scale id
     * @param string $grade the grade to be displayed
     * @param bool $short formats the grade in short form (result empty if grading is
     * not used, or no grade is available; parantheses are put around the grade if it is present)
     * @return string the formatted grade
     */
    public function format_grade($subject, $grade, $short = false) {
        if ($subject instanceof bookking_instance) {
            $scaleid = $subject->scale;
        } else {
            $scaleid = (int) $subject;
        }

        $result = '';
        if ($scaleid == 0 || is_null($grade) ) {
            // BookKing doesn't allow grading, or no grade entered.
            if (!$short) {
                $result = get_string('nograde');
            }
        } else {
            $grade = (int) $grade;
            if ($scaleid > 0) {
                // Numeric grade.
                $result .= $grade;
                if (strlen($grade) > 0) {
                    $result .= '/' . $scaleid;
                }
            } else {
                // Grade on scale.
                if ($grade > 0) {
                    $levels = $this->get_scale_levels(-$scaleid);
                    if (array_key_exists($grade, $levels)) {
                        $result .= $levels[$grade];
                    }
                }
            }
            if ($short && (strlen($result) > 0)) {
                $result = '('.$result.')';
            }
        }
        return $result;
    }

    /**
     * A utility function for producing grading lists (for use in formslib)
     *
     * Note that the selection list will contain a "nothing selected" option
     * with key -1 which will be displayed as "No grade".
     *
     * @param reference $bookking
     * @return array the choices to be displayed in a grade chooser
     */
    public function grading_choices($bookking) {
        if ($bookking->scale > 0) {
            $scalegrades = array();
            for ($i = 0; $i <= $bookking->scale; $i++) {
                $scalegrades[$i] = $i;
            }
        } else {
            $scaleid = - ($bookking->scale);
            $scalegrades = $this->get_scale_levels($scaleid);
        }
        $scalegrades = array(-1 => get_string('nograde')) + $scalegrades;
        return $scalegrades;
    }

    /**
     * Return a string describing the grading strategy of a bookking.
     *
     * @param int $strategy id number for the strategy
     * @return string description of the strategy
     */
    public function format_grading_strategy($strategy) {
        if ($strategy == BOOKKING_MAX_GRADE) {
            return get_string('maxgrade', 'bookking');
        } else {
            return get_string('meangrade', 'bookking');
        }
    }

    /**
     * Format a user-entered "note" on a slot or appointment, adjusting any links to embedded files.
     * The "note" may also be the booking instructions.
     *
     * @param string $content content of the note
     * @param int $format format of the note
     * @param context $context context of the note
     * @param string $area file ara for embedded files
     * @param int $itemid item id for embedded files
     * @return string the formatted note
     */
    public function format_notes($content, $format, $context, $area, $itemid) {
        $text = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id, 'mod_bookking', $area, $itemid);
        return format_text($text, $format);
    }

    /**
     * Format the notes relating to an appointment (appointment notes and confidential notes).
     *
     * @param bookking_instance $bookking the bookking in whose context the appointment is
     * @param stdClass $data database record describing the appointment
     * @param string $idfield the field in the record containing the item id
     * @return string formatted notes
     */
    public function format_appointment_notes(bookking_instance $bookking, $data, $idfield = 'id') {
        $note = '';
        $id = $data->{$idfield};
        if (isset($data->appointmentnote) && $bookking->uses_appointmentnotes()) {
            $note .= $this->format_notes($data->appointmentnote, $data->appointmentnoteformat, $bookking->get_context(),
                                         'appointmentnote', $id);
        }
        if (isset($data->teachernote) && $bookking->uses_teachernotes()) {
            $note .= $this->format_notes($data->teachernote, $data->teachernoteformat, $bookking->get_context(),
                                         'teachernote', $id);
        }
        return $note;
    }

    /**
     * Produce HTML code for a link to a user's profile.
     * That is, the full name of the user is displayed with a link to the user's course profile on it.
     *
     * @param bookking_instance $bookking the bookking in whose context the link is
     * @param stdClass $user the user to link to
     * @return string HTML code of the link
     */
    public function user_profile_link(bookking_instance $bookking, stdClass $user) {
        $profileurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $bookking->course));
        return html_writer::link($profileurl, fullname($user));
    }

    /**
     * Produce HTML code for a link to a user's appointment.
     * That is, the full name of the user is displayed with a link to a given appointment.
     *
     * @param unknown $bookking the bookking in whose context the link is
     * @param unknown $user the use in question
     * @param unknown $appointmentid id number of the appointment to link to
     * @return string HTML code of the link
     */
    public function appointment_link($bookking, $user, $appointmentid) {
        $paras = array(
                        'what' => 'viewstudent',
                        'id' => $bookking->cmid,
                        'appointmentid' => $appointmentid
        );
        $url = new moodle_url('/mod/bookking/view.php', $paras);
        return html_writer::link($url, fullname($user));
    }

    /**
     * Render a list of files in a filearea.
     *
     * @param int $contextid id number of the context of the files
     * @param string $filearea name of the file area
     * @param int $itemid item id in the file area
     * @return string rendered list of files
     */
    public function render_attachments($contextid, $filearea, $itemid) {

        $fs = get_file_storage();
        $o = '';

        // We retrieve all files according to the time that they were created.  In the case that several files were uploaded
        // at the sametime (e.g. in the case of drag/drop upload) we revert to using the filename.
        $files = $fs->get_area_files($contextid, 'mod_bookking', $filearea, $itemid, "filename", false);
        if ($files) {
            $o .= html_writer::start_tag('ul', array('class' => 'bookking_filelist'));
            foreach ($files as $file) {
                $filename = $file->get_filename();
                $pathname = $file->get_filepath();
                $mimetype = $file->get_mimetype();
                $iconimage = $this->pix_icon(file_file_icon($file), get_mimetype_description($file),
                                             'moodle', array('class' => 'icon'));
                $path = moodle_url::make_pluginfile_url($contextid, 'mod_bookking', $filearea, $itemid, $pathname, $filename);

                $ulitem = html_writer::link($path, $iconimage) . html_writer::link($path, s($filename));
                $o .= html_writer::tag('ul', $ulitem);
            }
            $o .= html_writer::end_tag('ul');
        }

        return $o;
    }


    /**
     * Render the module introduction of a bookking.
     *
     * @param bookking_instance $bookking the bookking in question
     * @return string rendered module info
     */
    public function mod_intro($bookking) {
        $o = $this->heading(format_string($bookking->name), 2);

        if (trim(strip_tags($bookking->intro))) {
            $o .= $this->box_start('mod_introbox');
            $o .= format_module_intro('bookking', $bookking->get_data(), $bookking->cmid);
            $o .= $this->box_end();
        }
        return $o;
    }

    /**
     * Construct a tab header in the teacher view.
     *
     * @param moodle_url $baseurl
     * @param string $namekey
     * @param string $what
     * @param string $subpage
     * @param string $nameargs
     * @return tabobject
     */
    private function teacherview_tab(moodle_url $baseurl, $namekey, $what, $subpage = '', $nameargs = null) {
        $taburl = new moodle_url($baseurl, array('what' => $what, 'subpage' => $subpage));
        $tabname = get_string($namekey, 'bookking', $nameargs);
        $id = ($subpage != '') ? $subpage : $what;
        $tab = new tabobject($id, $taburl, $tabname);
        return $tab;
    }

    /**
     * Render the tab header hierarchy in the teacher view.
     *
     * @param bookking_instance $bookking the bookking in question
     * @param moodle_url $baseurl base URL for the tab addresses
     * @param string $selected the selected tab
     * @param array $inactive any inactive tabs
     * @return string rendered tab tree
     */
    public function teacherview_tabs(bookking_instance $bookking, moodle_url $baseurl, $selected, $inactive = null) {

        $statstab = $this->teacherview_tab($baseurl, 'statistics', 'viewstatistics', 'overall');
        $statstab->subtree = array(
                        $this->teacherview_tab($baseurl, 'overall', 'viewstatistics', 'overall'),
                        $this->teacherview_tab($baseurl, 'studentbreakdown', 'viewstatistics', 'studentbreakdown'),
                        $this->teacherview_tab($baseurl, 'staffbreakdown', 'viewstatistics', 'staffbreakdown',
                                               $bookking->get_teacher_name()),
                        $this->teacherview_tab($baseurl, 'lengthbreakdown', 'viewstatistics', 'lengthbreakdown'),
                        $this->teacherview_tab($baseurl, 'groupbreakdown', 'viewstatistics', 'groupbreakdown')
        );

        $level1 = array(
                        $this->teacherview_tab($baseurl, 'myappointments', 'view', 'myappointments'),
                        $this->teacherview_tab($baseurl, 'allappointments', 'view', 'allappointments'),
                        $this->teacherview_tab($baseurl, 'datelist', 'datelist'),
                        $statstab,
                        $this->teacherview_tab($baseurl, 'export', 'export')
        );

        return $this->tabtree($level1, $selected, $inactive);
    }

    /**
     * Render a table of slots
     *
     * @param bookking_slot_table $slottable the table to rended
     * @return string the HTML output
     */
    public function render_bookking_slot_table(bookking_slot_table $slottable) {
        $table = new html_table();

        if ($slottable->showslot) {
            $table->head  = array(get_string('date', 'bookking'));
            $table->align = array('left');
        }
        if ($slottable->showstudent) {
            $table->head[]  = get_string('name');
            $table->align[] = 'left';
        }
        if ($slottable->showattended) {
            $table->head[] = get_string('seen', 'bookking');
            $table->align[] = 'center';
        }
        if ($slottable->showslot) {
            $table->head[]  = $slottable->bookking->get_teacher_name();
            $table->align[] = 'left';
        }
        if ($slottable->showslot && $slottable->showlocation) {
            $table->head[]  = get_string('location', 'bookking');
            $table->align[] = 'left';
        }

        $table->head[] = get_string('comments', 'bookking');
        $table->align[] = 'left';

        if ($slottable->showgrades) {
            $table->head[] = get_string('grade', 'bookking');
            $table->align[] = 'left';
        } else if ($slottable->hasotherstudents) {
            $table->head[] = get_string('otherstudents', 'bookking');
            $table->align[] = 'left';
        }
        if ($slottable->showactions) {
            $table->head[] = '';
            $table->align[] = 'right';
        }

        $table->data = array();

        foreach ($slottable->slots as $slot) {
            $rowdata = array();

            $studenturl = new moodle_url($slottable->actionurl, array('appointmentid' => $slot->appointmentid));

            $timedata = $this->userdate($slot->starttime);
            if ($slottable->showeditlink) {
                $timedata = $this->action_link($studenturl, $timedata);
            }
            $timedata = html_writer::div($timedata, 'datelabel');

            $starttime = $this->usertime($slot->starttime);
            $endtime   = $this->usertime($slot->endtime);
            $timedata .= html_writer::div("{$starttime} &ndash; {$endtime}", 'timelabel');

            if ($slottable->showslot) {
                $rowdata[] = $timedata;
            }

            if ($slottable->showstudent) {
                $name = fullname($slot->student);
                if ($slottable->showeditlink) {
                    $name = $this->action_link($studenturl, $name);
                }
                $rowdata[] = $name;
            }

            if ($slottable->showattended) {
                $iconid = $slot->attended ? 'ticked' : 'unticked';
                $iconhelp = $slot->attended ? 'seen' : 'notseen';
                $attendedpix = $this->pix_icon($iconid, get_string($iconhelp, 'bookking'), 'mod_bookking');
                $rowdata[] = $attendedpix;
            }

            if ($slottable->showslot) {
                $rowdata[] = $this->user_profile_link($slottable->bookking, $slot->teacher);
            }

            if ($slottable->showslot && $slottable->showlocation) {
                $rowdata[] = format_string($slot->location);
            }

            $notes = '';
            if ($slottable->showslot && isset($slot->slotnote)) {
                $notes .= $this->format_notes($slot->slotnote, $slot->slotnoteformat,
                                              $slottable->bookking->get_context(), 'slotnote', $slot->slotid);
            }
            $notes .= $this->format_appointment_notes($slottable->bookking, $slot, 'appointmentid');
            $rowdata[] = $notes;

            if ($slottable->showgrades || $slottable->hasotherstudents) {
                $gradedata = '';
                if ($slot->otherstudents) {
                    $gradedata = $this->render($slot->otherstudents);
                } else if ($slottable->showgrades) {
                    $gradedata = $this->format_grade($slottable->bookking, $slot->grade);
                }
                $rowdata[] = $gradedata;
            }
            if ($slottable->showactions) {
                $actions = '';
                if ($slot->canedit) {
                    $buttonurl = new moodle_url($slottable->actionurl,
                                     array('what' => 'editbooking', 'appointmentid' => $slot->appointmentid));
                    $button = new single_button($buttonurl, get_string('editbooking', 'bookking'));
                    $actions .= $this->render($button);
                }
                if ($slot->canview) {
                    $buttonurl = new moodle_url($slottable->actionurl,
                                     array('what' => 'viewbooking', 'appointmentid' => $slot->appointmentid));
                    $button = new single_button($buttonurl, get_string('viewbooking', 'bookking'));
                    $actions .= $this->render($button);
                }
                if ($slot->cancancel) {
                    $buttonurl = new moodle_url($slottable->actionurl,
                                     array('what' => 'cancelbooking', 'slotid' => $slot->slotid));
                    $button = new single_button($buttonurl, get_string('cancelbooking', 'bookking'));
                    $actions .= $this->render($button);
                }
                $rowdata[] = $actions;
            }
            $table->data[] = $rowdata;
        }

        return html_writer::table($table);
    }

    /**
     * Rendering a list of student, to be displayed within a larger table
     *
     * @param bookking_student_list $studentlist
     * @return string
     */
    public function render_bookking_student_list(bookking_student_list $studentlist) {

        $o = '';

        $toggleid = html_writer::random_id('toggle');

        if ($studentlist->expandable && count($studentlist->students) > 0) {
            $this->page->requires->yui_module('moodle-mod_bookking-studentlist',
                            'M.mod_bookking.studentlist.init',
                            array($toggleid, (boolean) $studentlist->expanded) );
            $imgclass = 'studentlist-togglebutton';
            $alttext = get_string('showparticipants', 'bookking');
            $o .= $this->output->pix_icon('t/switch', $alttext, 'moodle',
                            array('id' => $toggleid, 'class' => $imgclass));
        }

        $divprops = array('id' => 'list'.$toggleid);
        $o .= html_writer::start_div('studentlist', $divprops);
        if (count($studentlist->students) > 0) {
            $editable = $studentlist->actionurl && $studentlist->editable;
            if ($editable) {
                $o .= html_writer::start_tag('form', array('action' => $studentlist->actionurl,
                                'method' => 'post', 'class' => 'studentselectform'));
            }

            foreach ($studentlist->students as $student) {
                $class = 'otherstudent';
                $checkbox = '';
                if ($studentlist->checkboxname) {
                    if ($editable) {
                        $checkbox = html_writer::checkbox($studentlist->checkboxname, $student->entryid, $student->checked, '',
                                        array('class' => 'studentselect'));
                    } else {
                        $img = $student->checked ? 'ticked' : 'unticked';
                        $checkbox = $this->render(new pix_icon($img, '', 'bookking', array('class' => 'statictickbox')));
                    }
                }
                if ($studentlist->linkappointment) {
                    $name = $this->appointment_link($studentlist->bookking, $student->user, $student->entryid);
                } else {
                    $name = fullname($student->user);
                }
                $studicons = '';
                $studprovided = array();
                if ($student->notesprovided) {
                    $studprovided[] = get_string('message', 'bookking');
                }
                if ($student->filesprovided) {
                    $studprovided[] = get_string('nfiles', 'bookking', $student->filesprovided);
                }
                if ($studprovided) {
                    $providedstr = implode(', ', $studprovided);
                    $alttext = get_string('studentprovided', 'bookking', $providedstr);
                    $attachicon = new pix_icon('attachment', $alttext, 'bookking', array('class' => 'studdataicon'));
                    $studicons .= $this->render($attachicon);
                }

                if ($student->highlight) {
                    $class .= ' highlight';
                }
                $picture = $this->user_picture($student->user, array('courseid' => $studentlist->bookking->courseid));
                $grade = '';
                if ($studentlist->showgrades && $student->grade) {
                    $grade = $this->format_grade($studentlist->bookking, $student->grade, true);
                }
                $o .= html_writer::div($checkbox . $picture . ' ' . $name . $studicons . ' ' . $grade, $class);
            }

            if ($editable) {
                $o .= html_writer::empty_tag('input', array(
                                'type' => 'submit',
                                'class' => 'studentselectsubmit',
                                'value' => $studentlist->buttontext
                ));
                $o .= html_writer::end_tag('form');
            }
        }
        $o .= html_writer::end_div();

        return $o;
    }

    /**
     * Render a slot booker.
     *
     * @param bookking_slot_booker $booker
     * @return string
     */
    public function render_bookking_slot_booker(bookking_slot_booker $booker) {

        $table = new html_table();
        $table->head  = array( get_string('date', 'bookking'), get_string('start', 'bookking'),
                        get_string('end', 'bookking'), get_string('location', 'bookking'),
                        get_string('comments', 'bookking'), s($booker->bookking->get_teacher_name()),
                        get_string('groupsession', 'bookking'), '');
        $table->align = array ('left', 'left', 'left', 'left', 'left', 'left', 'left', 'left');
        $table->id = 'slotbookertable';
        $table->data = array();

        $previousdate = '';
        $previoustime = '';
        $previousendtime = '';
        $canappoint = false;

        foreach ($booker->slots as $slot) {

            $rowdata = array();

            $startdate = $this->userdate($slot->starttime);
            $starttime = $this->usertime($slot->starttime);
            $endtime = $this->usertime($slot->endtime);
            // Simplify display of dates, start and end times.
            if ($startdate == $previousdate && $starttime == $previoustime && $endtime == $previousendtime) {
                // If this row exactly matches previous, there's nothing to display.
                $startdatestr = '';
                $starttimestr = '';
                $endtimestr = '';
            } else if ($startdate == $previousdate) {
                // If this date matches previous date, just display times.
                $startdatestr = '';
                $starttimestr = $starttime;
                $endtimestr = $endtime;
            } else {
                // Otherwise, display all elements.
                $startdatestr = $startdate;
                $starttimestr = $starttime;
                $endtimestr = $endtime;
            }

            $rowdata[] = $startdatestr;
            $rowdata[] = $starttimestr;
            $rowdata[] = $endtimestr;

            $rowdata[] = format_string($slot->location);

            $rowdata[] = $this->format_notes($slot->notes, $slot->notesformat, $booker->bookking->get_context(),
                                             'slotnote', $slot->slotid);

            $rowdata[] = $this->user_profile_link($booker->bookking, $slot->teacher);

            $groupinfo = $slot->bookedbyme ? get_string('complete', 'bookking') : $slot->groupinfo;
            if ($slot->otherstudents) {
                $groupinfo .= $this->render($slot->otherstudents);
            }

            $rowdata[] = $groupinfo;

            if ($slot->canbook) {
                $bookaction = $booker->bookking->uses_bookingform() ? 'bookingform' : 'bookslot';
                $bookurl = new moodle_url($booker->actionurl, array('what' => $bookaction, 'slotid' => $slot->slotid));
                $button = new single_button($bookurl, get_string('bookslot', 'bookking'));
                $rowdata[] = $this->render($button);
            } else {
                $rowdata[] = '';
            }

            $table->data[] = $rowdata;

            $previoustime = $starttime;
            $previousendtime = $endtime;
            $previousdate = $startdate;
        }

        return html_writer::table($table);
    }

    /**
     * Render a command bar.
     *
     * @param bookking_command_bar $commandbar
     * @return string
     */
    public function render_bookking_command_bar(bookking_command_bar $commandbar) {
        $o = '';
        foreach ($commandbar->linkactions as $id => $action) {
            $this->add_action_handler($action, $id);
        }
        $o .= html_writer::start_div('commandbar');
        if ($commandbar->title) {
            $o .= html_writer::span($commandbar->title, 'title');
        }
        foreach ($commandbar->menus as $m) {
            $o .= $this->render($m);
        }
        $o .= html_writer::end_div();
        return $o;
    }

    /**
     * Render a slot manager.
     *
     * @param bookking_slot_manager $slotman
     * @return string
     */
    public function render_bookking_slot_manager(bookking_slot_manager $slotman) {

        $this->page->requires->yui_module('moodle-mod_bookking-saveseen',
                        'M.mod_bookking.saveseen.init', array($slotman->bookking->cmid) );

        $o = '';

        $table = new html_table();
        $table->head  = array('', get_string('date', 'bookking'), get_string('start', 'bookking'),
                        get_string('end', 'bookking'), get_string('location', 'bookking'), get_string('students', 'bookking') );
        $table->align = array ('center', 'left', 'left', 'left', 'left', 'left');
        if ($slotman->showteacher) {
            $table->head[] = s($slotman->bookking->get_teacher_name());
            $table->align[] = 'left';
        }
        $table->head[] = get_string('action', 'bookking');
        $table->align[] = 'center';

        $table->id = 'slotmanager';
        $table->data = array();

        $previousdate = '';
        $previoustime = '';
        $previousendtime = '';

        foreach ($slotman->slots as $slot) {

            $rowdata = array();

            $selectbox = html_writer::checkbox('selectedslot[]', $slot->slotid, false, '', array('class' => 'slotselect'));
            $rowdata[] = $slot->editable ? $selectbox : '';

            $startdate = $this->userdate($slot->starttime);
            $starttime = $this->usertime($slot->starttime);
            $endtime = $this->usertime($slot->endtime);
            // Simplify display of dates, start and end times.
            if ($startdate == $previousdate && $starttime == $previoustime && $endtime == $previousendtime) {
                // If this row exactly matches previous, there's nothing to display.
                $startdatestr = '';
                $starttimestr = '';
                $endtimestr = '';
            } else if ($startdate == $previousdate) {
                // If this date matches previous date, just display times.
                $startdatestr = '';
                $starttimestr = $starttime;
                $endtimestr = $endtime;
            } else {
                // Otherwise, display all elements.
                $startdatestr = $startdate;
                $starttimestr = $starttime;
                $endtimestr = $endtime;
            }

            $rowdata[] = $startdatestr;
            $rowdata[] = $starttimestr;
            $rowdata[] = $endtimestr;

            $rowdata[] = format_string($slot->location);

            $rowdata[] = $this->render($slot->students);

            if ($slotman->showteacher) {
                $rowdata[] = $this->user_profile_link($slotman->bookking, $slot->teacher);
            }

            $actions = '';
            if ($slot->editable) {
                $url = new moodle_url($slotman->actionurl, array('what' => 'deleteslot', 'slotid' => $slot->slotid));
                $confirmdelete = new confirm_action(get_string('confirmdelete-one', 'bookking'));
                $actions .= $this->action_icon($url, new pix_icon('t/delete', get_string('delete')), $confirmdelete);

                $url = new moodle_url($slotman->actionurl, array('what' => 'updateslot', 'slotid' => $slot->slotid));
                $actions .= $this->action_icon($url, new pix_icon('t/edit', get_string('edit')));
            }

            if ($slot->isattended || $slot->isappointed > 1) {
                $groupicon = 'i/groupevent';
            } else if ($slot->exclusivity == 1) {
                $groupicon = 't/groupn';
            } else {
                $groupicon = 't/groupv';
            }
            $groupalt = ''; $groupact = null;
            if ($slot->isattended) {
                $groupalt = 'attended';
            } else if ($slot->isappointed > 1) {
                $groupalt = 'isnonexclusive';
            } else if ($slot->editable) {
                if ($slot->exclusivity == 1) {
                    $groupact = array('what' => 'allowgroup', 'slotid' => $slot->slotid);
                    $groupalt = 'allowgroup';
                } else {
                    $groupact = array('what' => 'forbidgroup', 'slotid' => $slot->slotid);
                    $groupalt = 'forbidgroup';
                }
            } else {
                if ($slot->exclusivity == 1) {
                    $groupalt = 'allowgroup';
                } else {
                    $groupalt = 'forbidgroup';
                }
            }
            if ($groupact) {
                $url = new moodle_url($slotman->actionurl, $groupact);
                $actions .= $this->action_icon($url, new pix_icon($groupicon, get_string($groupalt, 'bookking')));
            } else {
                $actions .= $this->pix_icon($groupicon, get_string($groupalt, 'bookking'));
            }

            if ($slot->editable && $slot->isappointed) {
                $url = new moodle_url($slotman->actionurl, array('what' => 'revokeall', 'slotid' => $slot->slotid));
                $actions .= $this->action_icon($url, new pix_icon('s/no', get_string('revoke', 'bookking')));
            }

            if ($slot->exclusivity > 1) {
                $actions .= ' ('.$slot->exclusivity.')';
            }
            $rowdata[] = $actions;

            $table->data[] = $rowdata;

            $previoustime = $starttime;
            $previousendtime = $endtime;
            $previousdate = $startdate;
        }
        $o .= html_writer::table($table);

        return $o;
    }

    /**
     * Render a scheduling list.
     *
     * @param bookking_scheduling_list $list
     * @return string
     */
    public function render_bookking_scheduling_list(bookking_scheduling_list $list) {

        $mtable = new html_table();

        $mtable->id = $list->id;
        $mtable->head  = array ('', get_string('name'));
        $mtable->align = array ('center', 'left');
        foreach ($list->extraheaders as $field) {
            $mtable->head[] = $field;
            $mtable->align[] = 'left';
        }
        $mtable->head[] = get_string('action', 'bookking');
        $mtable->align[] = 'center';

        $mtable->data = array();
        foreach ($list->lines as $line) {
            $data = array($line->pix, $line->name);
            foreach ($line->extrafields as $field) {
                $data[] = $field;
            }
            $actions = '';
            if ($line->actions) {
                $menu = new action_menu($line->actions);
                $menu->actiontext = get_string('schedule', 'bookking');
                $actions = $this->render($menu);
            }
            $data[] = $actions;
            $mtable->data[] = $data;
        }
        return html_writer::table($mtable);
    }

    /**
     * Render total grade information.
     *
     * @param bookking_totalgrade_info $gradeinfo
     * @return string
     */
    public function render_bookking_totalgrade_info(bookking_totalgrade_info $gradeinfo) {
        $items = array();

        if ($gradeinfo->showtotalgrade) {
            $items[] = array('gradingstrategy', $this->format_grading_strategy($gradeinfo->bookking->gradingstrategy));
            $items[] = array('totalgrade', $this->format_grade($gradeinfo->bookking, $gradeinfo->totalgrade));
        }

        if (!is_null($gradeinfo->gbgrade)) {
            $gbgradeinfo = $this->format_grade($gradeinfo->bookking, $gradeinfo->gbgrade->grade);
            $attributes = array();
            if ($gradeinfo->gbgrade->hidden) {
                $attributes[] = get_string('hidden', 'grades');
            }
            if ($gradeinfo->gbgrade->locked) {
                $attributes[] = get_string('locked', 'grades');
            }
            if ($gradeinfo->gbgrade->overridden) {
                $attributes[] = get_string('overridden', 'grades');
            }
            if (count($attributes) > 0) {
                $gbgradeinfo .= ' ('.implode(', ', $attributes) .')';
            }
            $items[] = array('gradeingradebook', $gbgradeinfo);
        }

        $o = html_writer::start_div('totalgrade');
        $o .= html_writer::start_tag('dl', array('class' => 'totalgrade'));
        foreach ($items as $item) {
            $o .= html_writer::tag('dt', get_string($item[0], 'bookking'));
            $o .= html_writer::tag('dd', $item[1]);
        }
        $o .= html_writer::end_tag('dl');
        $o .= html_writer::end_div('totalgrade');
        return $o;
    }

    /**
     * Render a conflict list.
     *
     * @param bookking_conflict_list $cl
     * @return string
     */
    public function render_bookking_conflict_list(bookking_conflict_list $cl) {

        $o = html_writer::start_tag('ul');

        foreach ($cl->conflicts as $conflict) {
            $a = new stdClass();
            $a->datetime = userdate($conflict->starttime);
            $a->duration = $conflict->duration;
            if ($conflict->isself) {
                $entry = get_string('conflictlocal', 'bookking', $a);
            } else {
                $a->courseshortname = $conflict->courseshortname;
                $a->coursefullname = $conflict->coursefullname;
                $a->bookkingname = format_string($conflict->bookkingname);
                $entry = get_string('conflictremote', 'bookking', $a);
            }
            $o .= html_writer::tag('li', $entry);
        }

        $o .= html_writer::end_tag('ul');

        return $o;
    }


    /**
     * Render a table containing information about a booked appointment
     *
     * @param bookking_appointment_info $ai
     * @return string
     */
    public function render_bookking_appointment_info(bookking_appointment_info $ai) {
        $o = '';
        $o .= $this->output->container_start('appointmentinfotable');

        $o .= $this->output->box_start('boxaligncenter appointmentinfotable');

        $t = new html_table();

        if ($ai->showslotinfo) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('slotdatetimelabel', 'bookking'));
            $data = self::slotdatetime($ai->slot->starttime, $ai->slot->duration);
            $cell2 = new html_table_cell(get_string('slotdatetimelong', 'bookking', $data));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            $row = new html_table_row();
            $cell1 = new html_table_cell($ai->bookking->get_teacher_name());
            $cell2 = new html_table_cell(fullname($ai->slot->get_teacher()));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            if ($ai->slot->appointmentlocation) {
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('location', 'bookking'));
                $cell2 = new html_table_cell(format_string($ai->slot->appointmentlocation));
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }

            if ($ai->slot->notes) {
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('comments', 'bookking'));
                $notes = $this->format_notes($ai->slot->notes, $ai->slot->notesformat, $ai->bookking->get_context(),
                                              'slotnote', $ai->slot->id);
                $cell2 = new html_table_cell($notes);
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }
        }

        if ($ai->groupinfo) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('appointfor', 'bookking'));
            $cell2 = new html_table_cell(format_string($ai->groupinfo));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if ($ai->showbookinginfo) {
            if ($ai->bookking->has_bookinginstructions()) {
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('bookinginstructions', 'bookking'));
                $note = $this->format_notes($ai->bookking->bookinginstructions, $ai->bookking->bookinginstructionsformat,
                                            $ai->bookking->get_context(), 'bookinginstructions', 0);
                $cell2 = new html_table_cell($note);
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }
        }

        if ($ai->showstudentdata) {
            if ($ai->bookking->uses_studentnotes()) {
                $row = new html_table_row();
                if ($ai->onstudentside) {
                    $key = 'yourstudentnote';
                } else {
                    $key = 'studentnote';
                }
                $cell1 = new html_table_cell(get_string($key, 'bookking'));
                $note = format_text($ai->appointment->studentnote, $ai->appointment->studentnoteformat);
                $cell2 = new html_table_cell($note);
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }
            if ($ai->bookking->uses_studentfiles()) {
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('studentfiles', 'bookking'));
                $att = $this->render_attachments($ai->bookking->context->id, 'studentfiles', $ai->appointment->id);
                $cell2 = new html_table_cell($att);
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }
        }

        if ($ai->showresult) {
            if ($ai->bookking->uses_appointmentnotes() && $ai->appointment->appointmentnote) {
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('appointmentnotes', 'bookking'));
                $note = $this->format_notes($ai->appointment->appointmentnote, $ai->appointment->appointmentnoteformat,
                                            $ai->bookking->get_context(), 'appointmentnote', $ai->appointment->id);
                $cell2 = new html_table_cell($note);
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }
            if ($ai->bookking->uses_grades()) {
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('grade', 'bookking'));
                $gradetext = $this->format_grade($ai->bookking, $ai->appointment->grade, false);
                $cell2 = new html_table_cell($gradetext);
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }
        }

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        $o .= $this->output->container_end();
        return $o;
    }

}
