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

class backup_reflectivejournal_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        $reflectivejournal = new backup_nested_element('reflectivejournal', array('id'), array(
            'name', 'intro', 'introformat', 'days', 'grade', 'created', 'modified'));

        $entries = new backup_nested_element('entries');

        $entry = new backup_nested_element('entry', array('id'), array(
            'userid', 'created', 'modified', 'text', 'format', 'rating',
            'entrycomment', 'teacher', 'marked', 'mailed'));

        // journal -> entries -> entry
        $reflectivejournal->add_child($entries);
        $entries->add_child($entry);

        // Sources
        $reflectivejournal->set_source_table('reflectivejournal', array('id' => backup::VAR_ACTIVITYID));

        if ($this->get_setting_value('userinfo')) {
            $entry->set_source_table('reflectivejournal_entries', array('reflectivejournal' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $entry->annotate_ids('user', 'userid');
        $entry->annotate_ids('user', 'teacher');

        // Define file annotations
        $reflectivejournal->annotate_files('mod_reflectivejournal', 'intro', null); // This file areas haven't itemid
        $entry->annotate_files('mod_reflectivejournal_entries', 'text', null); // This file areas haven't itemid
        $entry->annotate_files('mod_reflectivejournal_entries', 'entrycomment', null); // This file areas haven't itemid

        return $this->prepare_activity_structure($reflectivejournal);
    }
}
