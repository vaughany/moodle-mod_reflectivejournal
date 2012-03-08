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
 * Reflective Journal view file
 *
 * @package    mod
 * @subpackage reflectivejournal
 * @copyright  2012 Paul Vaughan, based on work by David Monllaó and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);    // Course Module ID

if (! $cm = get_coursemodule_from_id('reflectivejournal', $id)) {
    print_error('Course Module ID was incorrect');
}

if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('Course is misconfigured');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course->id, true, $cm);

$entriesmanager = has_capability('mod/reflectivejournal:manageentries', $context);
$canadd = has_capability('mod/reflectivejournal:addentries', $context);

if (!$entriesmanager && !$canadd) {
    print_error('accessdenied', 'reflectivejournal');
}

if (! $reflectivejournal = $DB->get_record('reflectivejournal', array('id' => $cm->instance))) {
    print_error('Course module is incorrect');
}

if (! $cw = $DB->get_record('course_sections', array('id' => $cm->section))) {
    print_error('Course module is incorrect');
}


// Header
$PAGE->set_url('/mod/reflectivejournal/view.php', array('id'=>$id));
$PAGE->navbar->add(format_string($reflectivejournal->name));
$PAGE->set_title(format_string($reflectivejournal->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($reflectivejournal->name));

/// Check to see if groups are being used here
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);
groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/reflectivejournal/view.php?id='.$cm->id);


if ($entriesmanager) {
    $entrycount = reflectivejournal_count_entries($reflectivejournal, $currentgroup);

    echo '<div class="reportlink"><a href="report.php?id='.$cm->id.'">'.
    get_string('viewallentries', 'reflectivejournal', $entrycount).'</a></div>';

}

$reflectivejournal->intro = trim($reflectivejournal->intro);

if (!empty($reflectivejournal->intro)) {

    $intro = format_module_intro('reflectivejournal', $reflectivejournal, $cm->id);
    echo $OUTPUT->box($intro, 'generalbox', 'intro');
}

echo '<br />';

$timenow = time();
if ($course->format == 'weeks' and $reflectivejournal->days) {
    $timestart = $course->startdate + (($cw->section - 1) * 604800);
    if ($reflectivejournal->days) {
        $timefinish = $timestart + (3600 * 24 * $reflectivejournal->days);
    } else {
        $timefinish = $course->enddate;
    }
} else {  // Have no time limits on the journals

    $timestart = $timenow - 1;
    $timefinish = $timenow + 1;
    $reflectivejournal->days = 0;
}
if ($timenow > $timestart) {

    echo $OUTPUT->box_start();

    // Edit button
    if ($timenow < $timefinish) {

        if ($canadd) {
            echo '<div style="text-align: center;">';
            echo $OUTPUT->single_button('edit.php?id='.$cm->id, get_string('startoredit', 'reflectivejournal'), 'get');
            echo '</div>';
        }
    }

    // Display entry
    if ($entry = $DB->get_record('reflectivejournal_entries', array('userid' => $USER->id, 'reflectivejournal' => $reflectivejournal->id))) {
        if (empty($entry->text)) {
            echo '<p align="center"><b>'.get_string('blankentry', 'reflectivejournal').'</b></p>';
        } else {
            echo format_text($entry->text, $entry->format);
        }
    } else {
        echo '<span class="warning">'.get_string('notstarted', 'reflectivejournal').'</span>';
    }

    echo $OUTPUT->box_end();

    // Info
    if ($timenow < $timefinish) {
        if (!empty($entry->modified)) {
            echo '<div class="lastedit"><strong>'.get_string('lastedited').': </strong> ';
            echo userdate($entry->modified);
            echo ' ('.get_string('numwords', '', count_words($entry->text)).')';
            echo '</div>';
        }

        if (!empty($reflectivejournal->days)) {
            echo '<div class="editend"><strong>'.get_string('editingends', 'reflectivejournal').': </strong> ';
            echo userdate($timefinish).'</div>';
        }

    } else {
        echo '<div class="editend"><strong>'.get_string('editingended', 'reflectivejournal').': </strong> ';
        echo userdate($timefinish).'</div>';
    }

    // Feedback
    if (!empty($entry->entrycomment) or !empty($entry->rating)) {
        $grades = make_grades_menu($reflectivejournal->grade);
        echo $OUTPUT->heading(get_string('feedback'));
        reflectivejournal_print_feedback($course, $entry, $grades);
    }

} else {
    echo '<div class="warning">'.get_string('notopenuntil', 'reflectivejournal').': ';
    echo userdate($timestart).'</div>';
}

add_to_log($course->id, 'reflectivejournal', 'view', 'view.php?id='.$cm->id, $reflectivejournal->id, $cm->id);

echo $OUTPUT->footer();
