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
 * Reflective Journal
 *
 * @package    mod
 * @subpackage reflectivejournal
 * @copyright  2012 Paul Vaughan, based on work by David Monllaó and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/reflectivejournal/backup/moodle2/restore_reflectivejournal_stepslib.php');

class restore_reflectivejournal_activity_task extends restore_activity_task {

    protected function define_my_settings() {
        // none here
    }

    protected function define_my_steps() {
        $this->add_step(new restore_reflectivejournal_activity_structure_step('reflectivejournal_structure', 'reflectivejournal.xml'));
    }

    static public function define_decode_contents() {
        $contents = array();
        $contents[] = new restore_decode_content('reflectivejournal', array('intro'), 'reflectivejournal');
        $contents[] = new restore_decode_content('reflectivejournal_entries', array('text', 'entrycomment'), 'reflectivejournal_entry');

        return $contents;
    }

    static public function define_decode_rules() {
        $rules = array();
        $rules[] = new restore_decode_rule('JOURNALINDEX', '/mod/reflectivejournal/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('JOURNALVIEWBYID', '/mod/reflectivejournal/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('JOURNALREPORT', '/mod/reflectivejournal/report.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('JOURNALEDIT', '/mod/reflectivejournal/edit.php?id=$1', 'course_module');

        return $rules;
    }
}
