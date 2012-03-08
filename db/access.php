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
 * Reflective Journal capabilities setup
 *
 * @package    mod
 * @subpackage reflectivejournal
 * @copyright  2012 Paul Vaughan, based on work by David Monllaó and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$capabilities = array(

    'mod/reflectivejournal:manageentries' => array(

        'captype'       => 'write',
        'contextlevel'  => CONTEXT_MODULE,
        'archetypes'    => array(
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        )
    ),

    'mod/reflectivejournal:addentries' => array(

        'captype'       => 'write',
        'contextlevel'  => CONTEXT_MODULE,
        'archetypes'    => array(
            'student'           => CAP_ALLOW,
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        )
    )

);
