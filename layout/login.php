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
 * Layout - default.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require(__DIR__.'/header.php');
?>
<!-- moodle js hooks -->
<div id="page">
<div id="page-content">
<!--
////////////////////////// MAIN  ///////////////////////////////
-->
<main id="moodle-page" class="clearfix">
<div id="page-header" class="clearfix">
</div>

<section id="region-main">
<?php
if ($PAGE->title === get_string('restoredaccount')) {
    echo html_writer::start_div('loginerror-restoredaccount');
    echo $OUTPUT->main_content();
    echo html_writer::end_div();
} else {
    echo $OUTPUT->main_content();
}
?>
</section>
</main>
</div>
</div>
<!-- close moodle js hooks -->

<?php require(__DIR__.'/footer.php');
