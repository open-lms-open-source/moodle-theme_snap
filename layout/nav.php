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
 * Layout - nav.
 * This layout is baed on a moodle site index.php file but has been adapted to show news items in a different
 * way.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
?><header id='mr-nav' class='clearfix moodle-has-zindex'>
<div class="pull-right">
<?php $OUTPUT->print_fixed_menu(); ?>
</div>
<?php
    $sitefullname = format_string($SITE->fullname);
    if (!empty($PAGE->theme->settings->logo)) {
        $sitefullname = '<span class="sr-only">'.format_string($SITE->fullname).'</span>';
    }
    echo '<a aria-label="'.get_string('home', 'theme_snap').'" href="'. s($CFG->wwwroot).'" id="logo" title="'.s(format_string($SITE->fullname)).'">'.$sitefullname.'</a>';    
?>
</header>
