<?php

/**
 * Student bookking screen (where students choose appointments).
 *
 */

defined('MOODLE_INTERNAL') || die();

$appointgroup = optional_param('appointgroup', -1, PARAM_INT);

\mod_bookking\event\booking_form_viewed::create_from_bookking($bookking)->trigger();

$PAGE->set_docs_path('mod/bookking/studentview');

$urlparas = array(
        'id' => $bookking->cmid,
        'sesskey' => sesskey()
);
if ($appointgroup >= 0) {
    $urlparas['appointgroup'] = $appointgroup;
}
$actionurl = new moodle_url('/mod/bookking/view.php', $urlparas);


// General permissions check.
require_capability('mod/bookking:viewslots', $context);
$canbook = has_capability('mod/bookking:appoint', $context);
$canseefull = has_capability('mod/bookking:viewfullslots', $context);

if ($bookking->is_group_scheduling_enabled()) {
    $mygroupsforscheduling = groups_get_all_groups($bookking->courseid, $USER->id, $bookking->bookingrouping, 'g.id, g.name');
    if ($appointgroup > 0 && !array_key_exists($appointgroup, $mygroupsforscheduling)) {
        throw new moodle_exception('nopermissions');
    }
}

if ($bookking->is_group_scheduling_enabled()) {
    $canbook = $canbook && ($appointgroup >= 0);
} else {
    $appointgroup = 0;
}


include($CFG->dirroot.'/mod/bookking/studentview.controller.php');

echo $output->header();

// Print intro.
echo $output->mod_intro($bookking);


$showowngrades = $bookking->uses_grades();
// Print total grade (if any).
if ($showowngrades) {
    $totalgrade = $bookking->get_user_grade($USER->id);
    $gradebookinfo = $bookking->get_gradebook_info($USER->id);

    $showowngrades = !$gradebookinfo->hidden;

    if ($gradebookinfo && !$gradebookinfo->hidden && ($totalgrade || $gradebookinfo->overridden) ) {
        $grademsg = '';
        if ($gradebookinfo->overridden) {
            $grademsg = html_writer::tag('p',
                            get_string('overriddennotice', 'grades'),  array('class' => 'overriddennotice')
                        );
        } else {
            $grademsg = get_string('yourtotalgrade', 'bookking', $output->format_grade($bookking, $totalgrade));
        }
        echo html_writer::div($grademsg, 'totalgrade');
    }
}

// Print group selection menu if given.
if ($bookking->is_group_scheduling_enabled()) {
    $groupchoice = array();
    if ($bookking->is_individual_scheduling_enabled()) {
        $groupchoice[0] = get_string('myself', 'bookking');
    }
    foreach ($mygroupsforscheduling as $group) {
        $groupchoice[$group->id] = $group->name;
    }
    $select = $output->single_select($actionurl, 'appointgroup', $groupchoice, $appointgroup,
                                     array(-1 => 'choosedots'), 'appointgroupform');
    echo html_writer::div(get_string('appointforgroup', 'bookking', $select), 'dropdownmenu');
}

// Get past (attended) slots.

$pastslots = $bookking->get_attended_slots_for_student($USER->id);

if (count($pastslots) > 0) {
    $slottable = new bookking_slot_table($bookking, $showowngrades || $bookking->is_group_scheduling_enabled());
    foreach ($pastslots as $pastslot) {
        $appointment = $pastslot->get_student_appointment($USER->id);

        if ($pastslot->is_groupslot() && has_capability('mod/bookking:seeotherstudentsresults', $context)) {
            $others = new bookking_student_list($bookking, true);
            foreach ($pastslot->get_appointments() as $otherapp) {
                $othermark = $bookking->get_gradebook_info($otherapp->studentid);
                $gradehidden = !is_null($othermark) && ($othermark->hidden <> 0);
                $others->add_student($otherapp, $otherapp->studentid == $USER->id, false, !$gradehidden);
            }
        } else {
            $others = null;
        }
        $hasdetails = $bookking->uses_studentdata();
        $slottable->add_slot($pastslot, $appointment, $others, false, false, $hasdetails);
    }

    echo $output->heading(get_string('attendedslots', 'bookking'), 3);
    echo $output->render($slottable);
}


$upcomingslots = $bookking->get_upcoming_slots_for_student($USER->id);

if (count($upcomingslots) > 0) {
    $slottable = new bookking_slot_table($bookking, $showowngrades || $bookking->is_group_scheduling_enabled(), $actionurl);
    foreach ($upcomingslots as $slot) {
        $appointment = $slot->get_student_appointment($USER->id);

        if ($slot->is_groupslot() && has_capability('mod/bookking:seeotherstudentsbooking', $context)) {
            $showothergrades = has_capability('mod/bookking:seeotherstudentsresults', $context);
            $others = new bookking_student_list($bookking);
            foreach ($slot->get_appointments() as $otherapp) {
                $gradehidden = !$bookking->uses_grades() ||
                               ($bookking->get_gradebook_info($otherapp->studentid)->hidden <> 0) ||
                               (!$showothergrades && $otherapp->studentid <> $USER->id);
                $others->add_student($otherapp, $otherapp->studentid == $USER->id, false, !$gradehidden);
            }
        } else {
            $others = null;
        }

        $cancancel = $slot->is_in_bookable_period();
        $canedit = $cancancel && $bookking->uses_studentdata();
        $canview = !$cancancel && $bookking->uses_studentdata();
        if ($bookking->is_group_scheduling_enabled()) {
            $cancancel = $cancancel && ($appointgroup >= 0);
        }
        $slottable->add_slot($slot, $appointment, $others, $cancancel, $canedit, $canview);
    }

    echo $output->heading(get_string('upcomingslots', 'bookking'), 3);
    echo $output->render($slottable);
}

$bookablecnt = $bookking->count_bookable_appointments($USER->id, false);
$bookableslots = array_values($bookking->get_slots_available_to_student($USER->id, $canseefull));

if (!$canseefull && $bookablecnt == 0) {
    echo html_writer::div(get_string('canbooknofurtherappointments', 'bookking'), 'studentbookingmessage');

} else if (count($bookableslots) == 0) {

    // No slots are available at this time.
    $noslots = get_string('noslotsavailable', 'bookking');
    echo html_writer::div($noslots, 'studentbookingmessage');

} else {
    // The student can book (or see) further appointments, and slots are available.
    // Show the booking form.

    $booker = new bookking_slot_booker($bookking, $USER->id, $actionurl, $bookablecnt);

    $pagesize = 25;
    $total = count($bookableslots);
    $start = ($offset >= 0) ? $offset * $pagesize : 0;
    $end = $start + $pagesize;
    if ($end > $total) {
        $end = $total;
    }

    for ($idx = $start; $idx < $end; $idx++) {
        $slot = $bookableslots[$idx];
        $canbookthisslot = $canbook && ($bookablecnt != 0);

        if (has_capability('mod/bookking:seeotherstudentsbooking', $context)) {
            $others = new bookking_student_list($bookking, false);
            foreach ($slot->get_appointments() as $otherapp) {
                $others->add_student($otherapp, $otherapp->studentid == $USER->id);
            }
            $others->expandable = true;
            $others->expanded = false;
        } else {
            $others = null;
        }

        // Check what to print as group information...
        $remaining = $slot->count_remaining_appointments();
        if ($slot->exclusivity == 0) {
            $groupinfo = get_string('yes');
        } else if ($slot->exclusivity == 1 && $remaining == 1) {
            $groupinfo = get_string('no');
        } else {
            if ($remaining > 0) {
                $groupinfo = get_string('limited', 'bookking', $remaining.'/'.$slot->exclusivity);
            } else { // Group info should not be visible to students.
                $groupinfo = get_string('complete', 'bookking');
                $canbookthisslot = false;
            }
        }

        $booker->add_slot($slot, $canbookthisslot, false, $groupinfo, $others);
    }


    $msgkey = $bookking->has_slots_for_student($USER->id, true, false) ? 'welcomebackstudent' : 'welcomenewstudent';
    $bookingmsg1 = get_string($msgkey, 'bookking');

    $a = $bookablecnt;
    if ($bookablecnt == 0) {
        $msgkey = 'canbooknofurtherappointments';
    } else if ($bookablecnt == 1) {
        $msgkey = ($bookking->bookkingmode == 'oneonly') ? 'canbooksingleappointment' : 'canbook1appointment';
    } else if ($bookablecnt > 1) {
        $msgkey = 'canbooknappointments';
    } else {
        $msgkey = 'canbookunlimitedappointments';
    }
    $bookingmsg2 = get_string($msgkey, 'bookking', $a);

    echo $output->heading(get_string('availableslots', 'bookking'), 3);
    if ($canbook) {
        echo html_writer::div($bookingmsg1, 'studentbookingmessage');
        echo html_writer::div($bookingmsg2, 'studentbookingmessage');
    }
    if ($total > $pagesize) {
        echo $output->paging_bar($total, $offset, $pagesize, $actionurl, 'offset');
    }
    echo $output->render($booker);
    if ($total > $pagesize) {
        echo $output->paging_bar($total, $offset, $pagesize, $actionurl, 'offset');
    }

}

echo $output->footer();
