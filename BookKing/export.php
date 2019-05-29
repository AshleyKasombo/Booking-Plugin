<?php


defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/exportform.php');

$PAGE->set_docs_path('mod/bookking/export');

// Find active group in case that group mode is in use.
$currentgroupid = 0;
$groupmode = groups_get_activity_groupmode($bookking->cm);
if ($groupmode) {
    $currentgroupid = groups_get_activity_group($bookking->cm, true);
}

$actionurl = new moodle_url('/mod/bookking/view.php', array('what' => 'export', 'id' => $bookking->cmid));
$returnurl = new moodle_url('/mod/bookking/view.php', array('what' => 'view', 'id' => $bookking->cmid));
$PAGE->set_url($actionurl);
$mform = new bookking_export_form($actionurl, $bookking);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

$data = $mform->get_data();
if ($data) {
    $availablefields = bookking_get_export_fields($bookking);
    $selectedfields = array();
    foreach ($availablefields as $field) {
        $inputid = 'field-'.$field->get_id();
        if (isset($data->{$inputid}) && $data->{$inputid} == 1) {
            $selectedfields[] = $field;
            $field->set_renderer($output);
        }
    }
    $userid = $USER->id;
    if (isset($data->includewhom) && $data->includewhom == 'all') {
        require_capability('mod/bookking:canseeotherteachersbooking', $context);
        $userid = 0;
    }
    $pageperteacher = isset($data->paging) && $data->paging == 'perteacher';
    $preview = isset($data->preview);
} else {
    $preview = false;
}

if (!$data || $preview) {
    echo $OUTPUT->header();

    // Print top tabs.
    $taburl = new moodle_url('/mod/bookking/view.php', array('id' => $bookking->cmid, 'what' => 'export'));
    echo $output->teacherview_tabs($bookking, $taburl, 'export');

    if ($groupmode) {
        groups_print_activity_menu($bookking->cm, $taburl);
    }

    echo $output->heading(get_string('exporthdr', 'bookking'), 2);

    $mform->display();

    if ($preview) {
        $canvas = new bookking_html_canvas();
        $export = new bookking_export($canvas);

        $export->build($bookking,
                        $selectedfields,
                        $data->content,
                        $userid,
                        $currentgroupid,
                        $data->includeemptyslots,
                        $pageperteacher);

        $limit = 20;
        echo $canvas->as_html($limit, false);

        echo html_writer::div(get_string('previewlimited', 'bookking', $limit), 'previewlimited');
    }

    echo $output->footer();
    exit();
}

switch ($data->outputformat) {
    case 'csv':
        $canvas = new bookking_csv_canvas($data->csvseparator);
        break;
    case 'xls':
        $canvas = new bookking_excel_canvas();
        break;
    case 'ods':
        $canvas = new bookking_ods_canvas();
        break;
    case 'html':
        $canvas = new bookking_html_canvas($returnurl);
        break;
    case 'pdf':
        $canvas = new bookking_pdf_canvas($data->pdforientation);
        break;
}

$export = new bookking_export($canvas);

$export->build($bookking,
               $selectedfields,
               $data->content,
               $userid,
               $currentgroupid,
               $data->includeemptyslots,
               $pageperteacher);

$filename = clean_filename(format_string($course->shortname).'_'.format_string($bookking->name));
$canvas->send($filename);

