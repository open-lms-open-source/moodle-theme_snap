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
 * This layout is based on a Moodle site index.php file but has been adapted to show news items in a different
 * way.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use theme_snap\renderables\settings_link;
use theme_snap\renderables\genius_dashboard_link;

?>
<header id='mr-nav' class='clearfix moodle-has-zindex'>
<div id="snap-header">
<?php
// If the homepage is set to Dashboard, then the home icon link must redirect to dashboard.
$homepage = get_home_page();
if ($homepage === 1) {
    $defaulthomeurl = $CFG->wwwroot.'/my';
} else if ($homepage === 3) {
    $defaulthomeurl = $CFG->wwwroot.'/my/courses.php';
} else {
    $defaulthomeurl = $CFG->wwwroot;
}
$sitefullname = format_string($SITE->fullname);
$attrs = array(
    'aria-label' => get_string('home', 'theme_snap'),
    'id' => 'snap-home',
    'title' => $sitefullname,
);

if (!empty($PAGE->theme->settings->logo)) {
    $sitefullname = '<span class="sr-only">'.format_string($SITE->fullname).'</span>';
    $attrs['class'] = 'logo';
}

echo html_writer::link($defaulthomeurl, $sitefullname, $attrs);
?>

<div class="pull-right js-only row">
    <?php
    if (class_exists('local_geniusws\navigation')) {
        $bblink = new genius_dashboard_link();
        echo '<div id="genius_link_wrapper">';
        echo $OUTPUT->render($bblink);
        echo '</div>';
    }
    echo $OUTPUT->my_courses_nav_link();
    echo $OUTPUT->user_menu_nav_dropdown();
    echo $OUTPUT->render_message_icon();
    echo $OUTPUT->render_notification_popups();

    $settingslink = new settings_link();
    echo '<span class="hidden-md-down">';
    echo core_renderer::search_box();
    echo '</span>';
    echo $OUTPUT->render($settingslink);
    ?>
</div>
</div>
<?php
$custommenu = $OUTPUT->custom_menu();

/* Moodle custom menu. */
/* Hide it for the login index, login sign up and login forgot password pages. */
if (!empty($custommenu)) {
    if (!($PAGE->pagetype === 'login-index') &&
        !($PAGE->pagetype === 'login-signup') &&
        !($PAGE->pagetype === 'login-forgot_password')) {
        echo '<div id="snap-custom-menu-header">';
        echo $custommenu;
        echo '</div>';
    }
}
?>
</header>

<?php
echo $OUTPUT->personal_menu();
