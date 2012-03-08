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
require_once('lib.php');

$id = required_param('id', PARAM_INT);   // course

if (! $course = $DB->get_record('course', array('id' => $id))) {
    print_error('Course ID is incorrect');
}

require_course_login($course);


// Header
$strreflectivejournals = get_string('modulenameplural', 'reflectivejournal');
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/reflectivejournal/index.php', array('id' => $id));
$PAGE->navbar->add($strreflectivejournals);
$PAGE->set_title($strreflectivejournals);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($strreflectivejournals);

if (! $reflectivejournals = get_all_instances_in_course('reflectivejournal', $course)) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'reflectivejournal')), '../../course/view.php?id='.$course->id);
    die;
}

// Sections
$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $sections = get_all_sections($course->id);
}

$timenow = time();


// Table data
$table = new html_table();

$table->head = array();
$table->align = array();
if ($usesections) {
    $table->head[] = get_string('sectionname', 'format_'.$course->format);
    $table->align[] = 'center';
}

$table->head[] = get_string('name');
$table->align[] = 'left';
$table->head[] = get_string('description');
$table->align[] = 'left';

$currentsection = '';
$i = 0;
foreach ($reflectivejournals as $reflectivejournal) {

    $context = get_context_instance(CONTEXT_MODULE, $reflectivejournal->coursemodule);
    $entriesmanager = has_capability('mod/reflectivejournal:manageentries', $context);

    // Section
    $printsection = '';
    if ($reflectivejournal->section !== $currentsection) {
        if ($reflectivejournal->section) {
            $printsection = get_section_name($course, $sections[$reflectivejournal->section]);
        }
        if ($currentsection !== '') {
            $table->data[$i] = 'hr';
            $i++;
        }
        $currentsection = $reflectivejournal->section;
    }
    if ($usesections) {
        $table->data[$i][] = $printsection;
    }

    // Link
    if (!$reflectivejournal->visible) {
        //Show dimmed if the mod is hidden
        $table->data[$i][] = '<a class="dimmed" href="view.php?id='.$reflectivejournal->coursemodule.'">'.format_string($reflectivejournal->name, true).'</a>';
    } else {
        //Show normal if the mod is visible
        $table->data[$i][] = '<a href="view.php?id='.$reflectivejournal->coursemodule'.">'.format_string($reflectivejournal->name, true).'</a>';
    }

    // Description
    $table->data[$i][] = format_text($reflectivejournal->intro,  $reflectivejournal->introformat);

    // Entries info
    if ($entriesmanager) {

        // Display the report.php col only if is a entries manager in some CONTEXT_MODULE
        if (empty($managersomewhere)) {
            $table->head[] = get_string('viewentries', 'reflectivejournal');
            $table->align[] = 'left';
            $managersomewhere = true;

            // Fill the previous col cells
            $manageentriescell = count($table->head) - 1;
            for ($j = 0; $j < $i; $j++) {
                if (is_array($table->data[$j])) {
                    $table->data[$j][$manageentriescell] = '';
                }
            }
        }

        $entrycount = reflectivejournal_count_entries($reflectivejournal, get_current_group($course->id));
        $table->data[$i][] = '<a href="report.php?id='.$reflectivejournal->coursemodule.'">'.get_string('viewallentries', 'reflectivejournal', $entrycount).'</a>';
    } else if (!empty($managersomewhere)) {
        $table->data[$i][] = '';
    }

    $i++;
}

echo '<br />';

echo html_writer::table($table);

add_to_log($course->id, 'reflectivejournal', 'view all', 'index.php?id='.$course->id, '');

echo $OUTPUT->footer();
