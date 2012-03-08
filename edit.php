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
 * Reflective Journal plugin for Moodle 2.x
 *
 * @package    mod
 * @subpackage reflectivejournal
 * @copyright  2012 Paul Vaughan, based on work by David Monllaó and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once('./edit_form.php');

$id = required_param('id', PARAM_INT);    // Course Module ID

if (!$cm = get_coursemodule_from_id('reflectivejournal', $id)) {
    print_error('Course Module ID was incorrect');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('Course is misconfigured');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course->id, false, $cm);

require_capability('mod/reflectivejournal:addentries', $context);

if (! $reflectivejournal = $DB->get_record('reflectivejournal', array('id' => $cm->instance))) {
    print_error('Course module is incorrect');
}


// Header
$PAGE->set_url('/mod/reflectivejournal/edit.php', array('id' => $id));
$PAGE->navbar->add(get_string('edit'));
$PAGE->set_title(format_string($reflectivejournal->name));
$PAGE->set_heading($course->fullname);

$entry = $DB->get_record('reflectivejournal_entries', array('userid' => $USER->id, 'reflectivejournal' => $reflectivejournal->id));
if ($entry) {

    $data->text['text'] = $entry->text;
    if (can_use_html_editor()) {
        $data->text['format'] = FORMAT_HTML;
    } else {
        $data->text['format'] = FORMAT_MOODLE;
    }
}

$data->id = $cm->id;
$form = new mod_reflectivejournal_entry_form(null, array('current' => $data));

/// If data submitted, then process and store.
if ($fromform = $form->get_data()) {

    $timenow = time();

    // Common
    $newentry->text = $fromform->text['text'];
    $newentry->format = $fromform->text['format'];
    $newentry->modified = $timenow;

    if ($entry) {
        $newentry->id = $entry->id;
        if (!$DB->update_record('reflectivejournal_entries', $newentry)) {
            print_error('Could not update your reflective journal');
        }
        $logaction = 'update entry';

    } else {
        $newentry->userid = $USER->id;
        $newentry->journal = $reflectivejournal->id;
        if (!$newentry->id = $DB->insert_record('reflectivejournal_entries', $newentry)) {
            print_error('Could not insert a new reflective journal entry');
        }
        $logaction = 'add entry';
    }

    add_to_log($course->id, 'reflectivejournal', $logaction, 'view.php?id='.$cm->id, $newentry->id, $cm->id);

    redirect('view.php?id='.$cm->id);
    die;
}


echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($reflectivejournal->name));

$intro = format_module_intro('reflectivejournal', $reflectivejournal, $cm->id);
echo $OUTPUT->box($intro);

/// Otherwise fill and print the form.
$form->display();

echo $OUTPUT->footer();
