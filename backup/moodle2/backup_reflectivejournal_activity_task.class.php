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

require_once($CFG->dirroot.'/mod/reflectivejournal/backup/moodle2/backup_reflectivejournal_stepslib.php');

class backup_reflectivejournal_activity_task extends backup_activity_task {

    protected function define_my_settings() {
        // none here
    }

    protected function define_my_steps() {
        $this->add_step(new backup_reflectivejournal_activity_structure_step('reflectivejournal_structure', 'reflectivejournal.xml'));
    }

    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot.'/mod/reflectivejournal', '#');

        $pattern = "#(".$base."\/index.php\?id\=)([0-9]+)#";
        $content = preg_replace($pattern, '$@JOURNALINDEX*$2@$', $content);

        $pattern = "#(".$base."\/view.php\?id\=)([0-9]+)#";
        $content = preg_replace($pattern, '$@JOURNALVIEWBYID*$2@$', $content);

        $pattern = "#(".$base."\/report.php\?id\=)([0-9]+)#";
        $content = preg_replace($pattern, '$@JOURNALREPORT*$2@$', $content);

        $pattern = "#(".$base."\/edit.php\?id\=)([0-9]+)#";
        $content = preg_replace($pattern, '$@JOURNALEDIT*$2@$', $content);

        return $content;
    }
}
