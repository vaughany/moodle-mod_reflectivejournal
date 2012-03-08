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
 * Reflective Journal report
 *
 * @package    mod
 * @subpackage reflectivejournal
 * @copyright  2012 Paul Vaughan, based on work by David Monllaó and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);   // course module

if (! $cm = get_coursemodule_from_id('reflectivejournal', $id)) {
    print_error('Course Module ID was incorrect');
}

if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('Course module is misconfigured');
}

require_login($course->id, false, $cm);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_capability('mod/reflectivejournal:manageentries', $context);


if (! $reflectivejournal = $DB->get_record('reflectivejournal', array('id' => $cm->instance))) {
    print_error('Course module is incorrect');
}


// Header
$PAGE->set_url('/mod/reflectivejournal/report.php', array('id'=>$id));

$PAGE->navbar->add(get_string('entries', 'reflectivejournal'));
$PAGE->set_title(get_string('modulenameplural', 'reflectivejournal'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('entries', 'reflectivejournal'));


// make some easy ways to access the entries.
if ( $eee = $DB->get_records('reflectivejournal_entries', array('reflectivejournal' => $reflectivejournal->id))) {
    foreach ($eee as $ee) {
        $entrybyuser[$ee->userid] = $ee;
        $entrybyentry[$ee->id]  = $ee;
    }

} else {
    $entrybyuser  = array ();
    $entrybyentry = array ();
}

// Group mode
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);


/// Process incoming data if there is any
if ($data = data_submitted()) {

    $feedback = array();
    $data = (array)$data;

    // Peel out all the data from variable names.
    foreach ($data as $key => $val) {
        if ($key <> 'id') {
            $type = substr($key, 0, 1);
            $num  = substr($key, 1);
            $feedback[$num][$type] = $val;
        }
    }

    $timenow = time();
    $count = 0;
    foreach ($feedback as $num => $vals) {
        $entry = $entrybyentry[$num];
        // Only update entries where feedback has actually changed.
        if (($vals['r'] <> $entry->rating) || ($vals['c'] <> addslashes($entry->entrycomment))) {
            $newentry->rating           = $vals['r'];
            $newentry->entrycomment     = $vals['c'];
            $newentry->teacher          = $USER->id;
            $newentry->marked           = $timenow;
            $newentry->mailed           = 0;           // Make sure mail goes out (again, even)
            $newentry->id               = $num;
            if (!$DB->update_record('reflectivejournal_entries', $newentry)) {
                notify('Failed to update the reflective journal feedback for user '.$entry->userid);
            } else {
                $count++;
            }
            $entrybyuser[$entry->userid]->rating        = $vals['r'];
            $entrybyuser[$entry->userid]->entrycomment  = $vals['c'];
            $entrybyuser[$entry->userid]->teacher       = $USER->id;
            $entrybyuser[$entry->userid]->marked        = $timenow;

            $reflectivejournal = $DB->get_record('reflectivejournal', array('id' => $entrybyuser[$entry->userid]->reflectivejournal));
            $reflectivejournal->cmidnumber = $cm->idnumber;

            reflectivejournal_update_grades($reflectivejournal, $entry->userid);
        }
    }
    add_to_log($course->id, 'reflectivejournal', 'update feedback', 'report.php?id='.$cm->id, $count.' users', $cm->id);
    notify(get_string('feedbackupdated', 'reflectivejournal', $count), 'notifysuccess');

} else {
    add_to_log($course->id, 'reflectivejournal', 'view responses', 'report.php?id='.$cm->id, $reflectivejournal->id, $cm->id);
}

/// Print out the journal entries

if ($currentgroup) {
    $groups = $currentgroup;
} else {
    $groups = '';
}
$users = get_users_by_capability($context, 'mod/reflectivejournal:addentries', '', '', '', '', $groups);

if (!$users) {
    echo $OUTPUT->heading(get_string('nousersyet'));

} else {

    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/reflectivejournal/report.php?id='.$cm->id);

    $grades = make_grades_menu($reflectivejournal->grade);
    if (!$teachers = get_users_by_capability($context, 'mod/reflectivejournal:manageentries')) {
        print_error('noentriesmanagers', 'reflectivejournal');
    }

    $allowedtograde = (groups_get_activity_groupmode($cm) != VISIBLEGROUPS OR groups_is_member($currentgroup));

    if ($allowedtograde) {
        echo '<form action="report.php" method="post">';
    }

    if ($usersdone = reflectivejournal_get_users_done($reflectivejournal, $currentgroup)) {
        foreach ($usersdone as $user) {
            reflectivejournal_print_user_entry($course, $user, $entrybyuser[$user->id], $teachers, $grades);
            unset($users[$user->id]);
        }
    }

    foreach ($users as $user) {       // Remaining users
        reflectivejournal_print_user_entry($course, $user, null, $teachers, $grades);
    }

    if ($allowedtograde) {
        echo '  <div style="text-align: center;">';
        echo '    <input type="hidden" name="id" value="'.$cm->id.'" />';
        echo '    <input type="submit" value="'.get_string('saveallfeedback', 'reflectivejournal').'" />';
        echo '  </div>';
        echo '</form>';
    }
}

echo $OUTPUT->footer();
