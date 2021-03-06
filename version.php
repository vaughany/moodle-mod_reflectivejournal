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
 * Reflective Journal version details
 *
 * @package    mod
 * @subpackage reflectivejournal
 * @copyright  2012 Paul Vaughan, based on work by David Monllaó and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$module->version        = 2012031600;                   // The current plugin version (Date: YYYYMMDDXX)
$module->requires       = 2010112400;                   // Moodle 2.0+
$module->component      = 'mod_reflectivejournal';      // Full name of the plugin (used for diagnostics)
$module->cron           = 60;                           // Period for cron to check this plugin (secs)
$module->maturity       = MATURITY_BETA;
$module->release        = '0.1';
//$plugin->dependencies   = array();                      // Plugin dependencies
