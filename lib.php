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
 * Reflective Journal functions
 *
 * @package    mod
 * @subpackage reflectivejournal
 * @copyright  2012 Paul Vaughan, based on work by David Monllaó and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Standard module functions
 */

/**
 * Given an object containing all the necessary data, (defined by the form in mod.html) this function
 * will create a new instance and return the id number of the new instance.
 */
function reflectivejournal_add_instance($reflectivejournal) {
    global $DB;

    $reflectivejournal->modified = time();
    $reflectivejournal->id = $DB->insert_record('reflectivejournal', $reflectivejournal);

    reflectivejournal_grade_item_update($reflectivejournal);

    return $reflectivejournal->id;
}

/**
 * Given an object containing all the necessary data, (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 */
function reflectivejournal_update_instance($reflectivejournal) {
    global $DB;

    $reflectivejournal->modified = time();
    $reflectivejournal->id = $reflectivejournal->instance;

    $result = $DB->update_record('reflectivejournal', $reflectivejournal);

    reflectivejournal_grade_item_update($reflectivejournal);

    reflectivejournal_update_grades($reflectivejournal, 0, false);

    return $result;
}

/**
 * Given an ID of an instance of this module, this function will permanently delete the instance
 * and any data that depends on it.
 */
function reflectivejournal_delete_instance($id) {
    global $DB;

    $result = true;

    if (! $reflectivejournal = $DB->get_record('reflectivejournal', array('id' => $id))) {
        return false;
    }

    if (! $DB->delete_records('reflectivejournal_entries', array('reflectivejournal' => $reflectivejournal->id))) {
        $result = false;
    }

    if (! $DB->delete_records('reflectivejournal', array('id' => $reflectivejournal->id))) {
        $result = false;
    }

    return $result;
}

function reflectivejournal_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_RATE:                    return false;
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}

function reflectivejournal_get_view_actions() {
    return array('view', 'view all', 'view responses');
}

function reflectivejournal_get_post_actions() {
    return array('add entry', 'update entry', 'update feedback');
}

function reflectivejournal_user_outline($course, $user, $mod, $reflectivejournal) {
    global $DB;

    if ($entry = $DB->get_record('reflectivejournal_entries', array('userid' => $user->id, 'reflectivejournal' => $reflectivejournal->id))) {

        $numwords = count(preg_split("/\w\b/", $entry->text)) - 1;

        $result->info = get_string('numwords', '', $numwords);
        $result->time = $entry->modified;
        return $result;
    }
    return null;
}

function reflectivejournal_user_complete($course, $user, $mod, $reflectivejournal) {
    global $DB, $OUTPUT;

    if ($entry = $DB->get_record('reflectivejournal_entries', array('userid' => $user->id, 'reflectivejournal' => $reflectivejournal->id))) {

        echo $OUTPUT->box_start();

        if ($entry->modified) {
            echo '<p style="font-size: 60%;">'.get_string('lastedited').': '.userdate($entry->modified).'</p>';
        }
        if ($entry->text) {
            echo format_text($entry->text, $entry->format);
        }
        if ($entry->teacher) {
            $grades = make_grades_menu($reflectivejournal->grade);
            reflectivejournal_print_feedback($course, $entry, $grades);
        }

        echo $OUTPUT->box_end();

    } else {
        print_string('noentry', 'reflectivejournal');
    }
}

/**
 * Function to be run periodically according to the moodle cron
 * Finds all journal notifications that have yet to be mailed out, and mails them
 */
function reflectivejournal_cron () {
    global $CFG, $USER, $DB;

    $cutofftime = time() - $CFG->maxeditingtime;

    if ($entries = reflectivejournal_get_unmailed_graded($cutofftime)) {
        $timenow = time();

        foreach ($entries as $entry) {

            echo 'Processing Reflective Journal entry '.$entry->id."\n";

            if (! $user = $DB->get_record('user', array('id' => $entry->userid))) {
                echo 'Could not find user '.$entry->userid."\n";
                continue;
            }

            $USER->lang = $user->lang;

            if (! $course = $DB->get_record('course', array('id' => $entry->course))) {
                echo 'Could not find course '.$entry->course."\n";
                continue;
            }

            if (! $teacher = $DB->get_record('user', array('id' => $entry->teacher))) {
                echo 'Could not find teacher '.$entry->teacher."\n";
                continue;
            }

            if (! $mod = get_coursemodule_from_instance('reflectivejournal', $entry->reflectivejournal, $course->id)) {
                echo 'Could not find course module for reflective journal id '.$entry->reflectivejournal."\n";
                continue;
            }

            $context = get_context_instance(CONTEXT_MODULE, $mod->id);
            $canadd = has_capability('mod/reflectivejournal:addentries', $context, $user);
            $entriesmanager = has_capability('mod/reflectivejournal:manageentries', $context, $user);

            if (!$canadd and $entriesmanager) {
                continue;  // Not an active participant
            }

            unset($reflectivejournalinfo);
            $reflectivejournalinfo->teacher = fullname($teacher);
            $reflectivejournalinfo->journal = format_string($entry->name, true);
            $reflectivejournalinfo->url = $CFG->wwwroot.'/mod/reflectivejournal/view.php?id='.$mod->id;
            $modnamepl = get_string( 'modulenameplural', 'reflectivejournal' );
            $msubject = get_string( 'mailsubject', 'reflectivejournal' );

            $postsubject = $course->shortname.': '.$msubject.': '.format_string($entry->name, true);
            $posttext  = $course->shortname.' -> '.$modnamepl.' -> '.format_string($entry->name, true)."\n";
            $posttext .= '---------------------------------------------------------------------'."\n";
            $posttext .= get_string('journalmail', 'reflectivejournal', $reflectivejournalinfo)."\n";
            $posttext .= '---------------------------------------------------------------------'."\n";
            if ($user->mailformat == 1) {  // HTML
                $posthtml = '<p>'.
                    '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->shortname.'</a> -> '.
                    '<a href="'.$CFG->wwwroot.'/mod/reflectivejournal/index.php?id='.$course->id.'">Reflective Journal</a> -> '.
                    '<a href="'.$CFG->wwwroot.'/mod/reflectivejournal/view.php?id='.$mod->id.'">'.format_string($entry->name, true).'</a></p>';
                $posthtml .= '<hr />';
                $posthtml .= '<p>'.get_string('journalmailhtml', 'reflectivejournal', $reflectivejournalinfo).'</p>';
                $posthtml .= '<hr />';
            } else {
                $posthtml = '';
            }

            if (! email_to_user($user, $teacher, $postsubject, $posttext, $posthtml)) {
                echo 'Error: Reflective Journal cron: Could not send out mail for id '.$entry->id.' to user '.$user->id.' ('.$user->email.")\n";
            }
            if (!$DB->set_field('reflectivejournal_entries', 'mailed', '1', array('id' => $entry->id))) {
                echo 'Could not update the mailed field for id '.$entry->id."\n";
            }
        }
    }

    return true;
}

function reflectivejournal_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG, $DB, $OUTPUT;

    if (!get_config('reflectivejournal', 'showrecentactivity')) {
        return false;
    }

    $content = false;
    $reflectivejournals = null;

    // log table should not be used here

    $select = "time > ?
               AND course = ?
               AND module = 'reflectivejournal'
               AND (action = 'add entry'
               OR action = 'update entry')";

    if (!$logs = $DB->get_records_select('log', $select, array($timestart, $course->id), 'time ASC')) {
        return false;
    }

    $modinfo = & get_fast_modinfo($course);
    foreach ($logs as $log) {
        // Get reflective journal info.  I'll need it later
        $j_log_info = reflectivejournal_log_info($log);

        $cm = $modinfo->instances['reflectivejournal'][$j_log_info->id];
        if (!$cm->uservisible) {
            continue;
        }

        if (!isset($reflectivejournals[$log->info])) {
            $reflectivejournals[$log->info] = $j_log_info;
            $reflectivejournals[$log->info]->time = $log->time;
            $reflectivejournals[$log->info]->url = str_replace('&', '&amp;', $log->url);
        }
    }

    if ($reflectivejournals) {
        $content = true;
        echo $OUTPUT->heading(get_string('newjournalentries', 'reflectivejournal').':', 3);
        foreach ($reflectivejournals as $reflectivejournal) {
            print_recent_activity_note($reflectivejournal->time, $reflectivejournal, $reflectivejournal->name,
                                       $CFG->wwwroot.'/mod/reflectivejournal/'.$reflectivejournal->url);
        }
    }

    return $content;
}

/**
 * Returns the users with data in one journal (users with records in journal_entries, students and teachers)
 */
function reflectivejournal_get_participants($reflectivejournalid) {
    global $DB;

    //Get students
    $students = $DB->get_records_sql("SELECT DISTINCT u.id
                                      FROM {user} u,
                                      {reflectivejournal_entries} j
                                      WHERE j.reflectivejournal = '$reflectivejournalid'
                                      AND u.id = j.userid");
    //Get teachers
    $teachers = $DB->get_records_sql("SELECT DISTINCT u.id
                                      FROM {user} u,
                                      {reflectivejournal_entries} j
                                      WHERE j.reflectivejournal = '$reflectivejournalid'
                                      AND u.id = j.teacher");

    //Add teachers to students
    if ($teachers) {
        foreach ($teachers as $teacher) {
            $students[$teacher->id] = $teacher;
        }
    }
    //Return students array (it contains an array of unique users)
    return ($students);
}

/**
 * This function returns if a scale is being used by one journal
 */
function reflectivejournal_scale_used ($reflectivejournalid, $scaleid) {
    global $DB;
    $return = false;

    $rec = $DB->get_record('reflectivejournal', array('id' => $reflectivejournalid, 'grade' => -$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Checks if scale is being used by any instance of journal
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any journal
 */
function reflectivejournal_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->get_records('reflectivejournal', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the journal.
 *
 * @param object $mform form passed by reference
 */
function reflectivejournal_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'reflectivejournalheader', get_string('modulenameplural', 'reflectivejournal'));
    $mform->addElement('advcheckbox', 'reset_reflectivejournal', get_string('removemessages', 'reflectivejournal'));
}

/**
 * Course reset form defaults.
 *
 * @param object $course
 * @return array
 */
function reflectivejournal_reset_course_form_defaults($course) {
    return array('reset_reflectivejournal' => 1);
}

/**
 * Removes all entries
 *
 * @param object $data
 */
function reflectivejournal_reset_userdata($data) {
    global $CFG, $DB;

    $status = array();
    if (!empty($data->reset_reflectivejournal)) {

        $sql = "SELECT j.id
                FROM {reflectivejournal} j
                WHERE j.course = ?";
        $params = array($data->courseid);

        $DB->delete_records_select('reflectivejournal_entries', 'reflectivejournal IN ('.$sql.')', $params);

        $status[] = array('component' => get_string('modulenameplural', 'reflectivejournal'),
                          'item' => get_string('removeentries', 'reflectivejournal'),
                          'error' => false);
    }

    return $status;
}

function reflectivejournal_print_overview($courses, &$htmlarray) {
    global $USER, $CFG, $DB;

    if (!get_config('reflectivejournal', 'overview')) {
        return array();
    }

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$reflectivejournals = get_all_instances_in_courses('reflectivejournal', $courses)) {
        return array();
    }

    $strreflectivejournal = get_string('modulename', 'reflectivejournal');

    $timenow = time();
    foreach ($reflectivejournals as $reflectivejournal) {

        $courses[$reflectivejournal->course]->format = $DB->get_field('course', 'format', array('id' => $reflectivejournal->course));

        if ($courses[$reflectivejournal->course]->format == 'weeks' AND $reflectivejournal->days) {

            $coursestartdate = $courses[$reflectivejournal->course]->startdate;

            $reflectivejournal->timestart  = $coursestartdate + (($reflectivejournal->section - 1) * 608400);
            if (!empty($reflectivejournal->days)) {
                $reflectivejournal->timefinish = $reflectivejournal->timestart + (3600 * 24 * $reflectivejournal->days);
            } else {
                $reflectivejournal->timefinish = 9999999999;
            }
            $reflectivejournalopen = ($reflectivejournal->timestart < $timenow && $timenow < $reflectivejournal->timefinish);

        } else {
            $reflectivejournalopen = true;
        }

        if ($reflectivejournalopen) {
            $str = '<div class="reflectivejournal overview"><div class="name">'.
                $strreflectivejournal.': <a '.($reflectivejournal->visible ? '' : ' class="dimmed"').
                ' href="'.$CFG->wwwroot.'/mod/reflectivejournal/view.php?id='.$reflectivejournal->coursemodule.'">'.
                $reflectivejournal->name.'</a></div></div>';
            if (empty($htmlarray[$reflectivejournal->course]['reflectivejournal'])) {
                $htmlarray[$reflectivejournal->course]['reflectivejournal'] = $str;
            } else {
                $htmlarray[$reflectivejournal->course]['reflectivejournal'] .= $str;
            }
        }
    }
}

function reflectivejournal_get_user_grades($reflectivejournal, $userid=0) {
    global $DB;

    if ($userid) {
        $userstr = 'AND userid = '.$userid;
    } else {
        $userstr = '';
    }

    if (!$reflectivejournal) {
        return false;

    } else {

        $sql = "SELECT userid, modified AS datesubmitted, format AS feedbackformat,
                rating AS rawgrade, entrycomment AS feedback, teacher AS usermodifier, marked AS dategraded
                FROM {reflectivejournal_entries}
                WHERE reflectivejournal = '".$reflectivejournal->id."' ".$userstr;

        $grades = $DB->get_records_sql($sql);

        if ($grades) {
            foreach ($grades as $key => $grade) {
                $grades[$key]->id = $grade->userid;
            }
        } else {
            return false;
        }

        return $grades;
    }

}

/**
 * Create grade item for given reflective journal
 *
 * @param object $journal object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function reflectivejournal_grade_item_update($reflectivejournal, $grades=null) {
    global $CFG;
    if (!function_exists('reflectivejournal_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (array_key_exists('cmidnumber', $reflectivejournal)) {
        $params = array('itemname'=>$reflectivejournal->name, 'idnumber'=>$reflectivejournal->cmidnumber);
    } else {
        $params = array('itemname'=>$reflectivejournal->name);
    }

    if ($reflectivejournal->grade > 0) {
        $params['gradetype']  = GRADE_TYPE_VALUE;
        $params['grademax']   = $reflectivejournal->grade;
        $params['grademin']   = 0;
        $params['multfactor'] = 1.0;

    } else if ($reflectivejournal->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$reflectivejournal->grade;

    } else {
        $params['gradetype']  = GRADE_TYPE_NONE;
        $params['multfactor'] = 1.0;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/reflectivejournal', $reflectivejournal->course, 'mod', 'reflectivejournal', $reflectivejournal->id, 0, $grades, $params);
}


/**
 * Delete grade item for given journal
 *
 * @param   object   $journal
 * @return  object   grade_item
 */
function reflectivejournal_grade_item_delete($reflectivejournal) {
    global $CFG;

    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/reflectivejournal', $reflectivejournal->course, 'mod', 'reflectivejournal', $reflectivejournal->id, 0, null, array('deleted' => 1));
}


/**
 * SQL functions
 */

function reflectivejournal_get_users_done($reflectivejournal, $currentgroup) {
    global $DB;

    $sql = "SELECT u.*
            FROM {reflectivejournal_entries} j
            JOIN {user} u ON j.userid = u.id ";

    // Group users
    if ($currentgroup != 0) {
        $sql.= "JOIN {groups_members} gm
                ON gm.userid = u.id
                AND gm.groupid = '".$currentgroup."'";
    }

    $sql.= " WHERE j.reflectivejournal = '".$reflectivejournal->id."' ORDER BY j.modified DESC";
    $reflectivejournals = $DB->get_records_sql($sql);

    $cm = reflectivejournal_get_coursemodule($reflectivejournal->id);
    if (!$reflectivejournals || !$cm) {
        return null;
    }

    // remove unenrolled participants
    foreach ($reflectivejournals as $key => $user) {

        $context = get_context_instance(CONTEXT_MODULE, $cm->id);

        $canadd = has_capability('mod/reflectivejournal:addentries', $context, $user);
        $entriesmanager = has_capability('mod/reflectivejournal:manageentries', $context, $user);

        if (!$entriesmanager and !$canadd) {
            unset($reflectivejournals[$key]);
        }
    }

    return $reflectivejournals;
}

/**
 * Counts all the journal entries (optionally in a given group)
 */
function reflectivejournal_count_entries($reflectivejournal, $groupid = 0) {
    global $DB;

    $cm = reflectivejournal_get_coursemodule($reflectivejournal->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if ($groupid) {     /// How many in a particular group?

        $sql = "SELECT DISTINCT u.id FROM {reflectivejournal_entries} j
                JOIN {groups_members} g ON g.userid = j.userid
                JOIN {user} u ON u.id = g.userid
                WHERE j.reflectivejournal = ".$reflectivejournal->id." AND g.groupid = '".$groupid."'";
        $reflectivejournals = $DB->get_records_sql($sql);

    } else { /// Count all the entries from the whole course

        $sql = "SELECT DISTINCT u.id FROM {reflectivejournal_entries} j
                JOIN {user} u ON u.id = j.userid
                WHERE j.reflectivejournal = '".$reflectivejournal->id."'";
        $reflectivejournals = $DB->get_records_sql($sql);
    }

    if (!$reflectivejournals) {
        return 0;
    }

    // remove unenrolled participants
    foreach ($reflectivejournals as $key => $user) {

        $canadd = has_capability('mod/reflectivejournal:addentries', $context, $user);
        $entriesmanager = has_capability('mod/reflectivejournal:manageentries', $context, $user);

        if (!$entriesmanager && !$canadd) {
            unset($reflectivejournals[$key]);
        }
    }

    return count($reflectivejournals);
}

function reflectivejournal_get_unmailed_graded($cutofftime) {
    global $DB;

    $sql = "SELECT je.*, j.course, j.name
            FROM {reflectivejournal_entries} je
            JOIN {reflectivejournal} j ON je.reflectivejournal = j.id
            WHERE je.mailed = '0'
            AND je.marked < '".$cutofftime."'
            AND je.marked > 0";
    return $DB->get_records_sql($sql);
}

function reflectivejournal_log_info($log) {
    global $DB;

    $sql = "SELECT j.*, u.firstname, u.lastname
            FROM {reflectivejournal} j
            JOIN {reflectivejournal_entries} je ON je.reflectivejournal = j.id
            JOIN {user} u ON u.id = je.userid
            WHERE je.id = '".$log->info."'";
    return $DB->get_record_sql($sql);
}

/**
 * Returns the journal instance course_module id
 *
 * @param integer $journal
 * @return object
 */
function reflectivejournal_get_coursemodule($reflectivejournalid) {
    global $DB;

    return $DB->get_record_sql("SELECT cm.id FROM {course_modules} cm
                                JOIN {modules} m ON m.id = cm.module
                                WHERE cm.instance = '".$reflectivejournalid."'
                                AND m.name = 'reflectivejournal'");
}

/**
 * Other Reflective Journal functions
 */

function reflectivejournal_print_user_entry($course, $user, $entry, $teachers, $grades) {
    global $USER, $OUTPUT, $DB, $CFG;

    require_once($CFG->dirroot.'/lib/gradelib.php');

    echo '<table border="1" cellspacing="0" valign="top" cellpadding="10">';
    echo '  <tr>';
    echo '    <td rowspan="2" width="35" valign="top">';
    echo $OUTPUT->user_picture($user, array('courseid' => $course->id));
    echo '    </td>';
    echo '    <td nowrap="nowrap" width="100%">'.fullname($user);
    if ($entry) {
        echo '      <span style="font-size: 60%;">'.get_string('lastedited').': '.userdate($entry->modified).'</span>';
    }
    echo '    </td>';
    echo '  </tr>';

    echo '  <tr>';
    echo '    <td width="100%">';
    if ($entry) {
        echo format_text($entry->text, $entry->format);
    } else {
        print_string('noentry', 'reflectivejournal');
    }
    echo '    </td>';
    echo '  </tr>';

    if ($entry) {
        echo '  <tr>';
        echo '    <td width="35" valign="top">';
        if (!$entry->teacher) {
            $entry->teacher = $USER->id;
        }
        if (empty($teachers[$entry->teacher])) {
            $teachers[$entry->teacher] = $DB->get_record('user', array('id' => $entry->teacher));
        }
        echo $OUTPUT->user_picture($teachers[$entry->teacher], array('courseid' => $course->id));
        echo '    </td>';
        echo '    <td>'.get_string('feedback').':';

        $attrs = array();
        $hiddengradestr = '';
        $gradebookgradestr = '';
        $feedbackdisabledstr = '';
        $feedbacktext = $entry->entrycomment;

        // If the grade was modified from the gradebook disable edition
        $grading_info = grade_get_grades($course->id, 'mod', 'reflectivejournal', $entry->reflectivejournal, array($user->id));
        if ($gradingdisabled = $grading_info->items[0]->grades[$user->id]->locked || $grading_info->items[0]->grades[$user->id]->overridden) {
            $attrs['disabled'] = 'disabled';
            $hiddengradestr = '<input type="hidden" name="r'.$entry->id.'" value="'.$entry->rating.'"/>';
            $gradebooklink = '<a href="'.$CFG->wwwroot.'/grade/report/grader/index.php?id='.$course->id.'">';
            $gradebooklink.= $grading_info->items[0]->grades[$user->id]->str_long_grade.'</a>';
            $gradebookgradestr = '<br/>'.get_string('gradeingradebook', 'reflectivejournal').':&nbsp;'.$gradebooklink;

            $feedbackdisabledstr = 'disabled="disabled"';
            $feedbacktext = $grading_info->items[0]->grades[$user->id]->str_feedback;
        }

        // Grade selector
        echo html_writer::select($grades, 'r'.$entry->id, $entry->rating, get_string('nograde').'...', $attrs);
        echo $hiddengradestr;
        if ($entry->marked) {
            echo '<span style="font-size: 60%;">'.userdate($entry->marked).'</span>';
        }
        echo $gradebookgradestr;

        // Feedback text
        echo '    <br />';
        echo '    <textarea name="c'.$entry->id.'" rows="12" cols="60" wrap="virtual" '.$feedbackdisabledstr.'>';
        p($feedbacktext);
        echo '    </textarea>';
        echo '    <br />';

        if ($feedbackdisabledstr != '') {
            echo '    <input type="hidden" name="c'.$entry->id.'" value="'.$feedbacktext.'"/>';
        }
        echo '    </td>';
        echo '  </tr>';
    }
    echo '</table><br clear="all" />';

}

function reflectivejournal_print_feedback($course, $entry, $grades) {
    global $CFG, $DB, $OUTPUT;

    require_once($CFG->dirroot.'/lib/gradelib.php');

    if (! $teacher = $DB->get_record('user', array('id' => $entry->teacher))) {
        print_error('Weird reflective journal error');
    }

    echo '<table cellspacing="0" align="center" class="feedbackbox">';

    echo '  <tr>';
    echo '    <td class="left picture">';
    echo $OUTPUT->user_picture($teacher, array('courseid' => $course->id));
    echo '    </td>';
    echo '    <td class="entryheader">';
    echo '      <span class="author">'.fullname($teacher).'</span>';
    echo '      <span class="time">'.userdate($entry->marked).'</span>';
    echo '    </td>';
    echo '  </tr>';

    echo '  <tr>';
    echo '    <td class="left side">&nbsp;</td>';
    echo '    <td class="entrycontent">';

    echo '      <div class="grade">';

    // Gradebook preference
    if ($grading_info = grade_get_grades($course->id, 'mod', 'reflectivejournal', $entry->reflectivejournal, array($entry->userid))) {
        echo get_string('grade').': ';
        echo $grading_info->items[0]->grades[$entry->userid]->str_long_grade;
    } else {
        print_string('nograde');
    }
    echo '      </div>';

    // Feedback text
    echo format_text($entry->entrycomment);
    echo '    </td>';
    echo '  </tr>';
    echo '</table>';
}

