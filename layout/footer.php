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
 * Layout - footer.
 * This layout is baed on a moodle site index.php file but has been adapted to show news items in a different
 * way.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$inccoursefooterclass = ($PAGE->theme->settings->coursefootertoggle && strpos($PAGE->pagetype, 'course-view-') === 0) ? ' hascoursefooter' : ' nocoursefooter';
?>
<footer id="moodle-footer" role="contentinfo" class="clearfix<?php echo ($inccoursefooterclass)?>">
<?php
/* snap custom footer */

/* custom footer edit button - always shown */
$footnote = empty($PAGE->theme->settings->footnote) ? '' : $PAGE->theme->settings->footnote;
if ($this->page->user_is_editing() && $PAGE->pagetype == 'site-index') {
    $url = new moodle_url('/admin/settings.php', ['section' => 'themesettingsnap'], 'admin-footnote');
    $link = html_writer::link($url, get_string('editcustomfooter', 'theme_snap'), ['class' => 'btn btn-inverse btn-sm']);
    $footnote .= '<p class="text-right">'.$link.'</p>';
}

/* custom menu edit button - only shown if menu exists */
$custommenu = $OUTPUT->custom_menu();
if (!empty($custommenu) && $this->page->user_is_editing() && $PAGE->pagetype == 'site-index') {
    $url = new moodle_url('/admin/settings.php', ['section' => 'themesettings'], 'id_s__custommenuitems');
    $link = html_writer::link($url, get_string('editcustommenu', 'theme_snap'), ['class' => 'btn btn-inverse btn-sm']);
    $custommenu .= '<p class="text-right">'.$link.'</p>';
}

if (!empty($custommenu) && !empty($footnote)) {
    echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo $footnote;
        echo '</div>';
        echo '<div class="col-md-6">';
        echo $custommenu;
        echo '</div>';
    echo '</div>';
} else if (!empty($footnote)) {
    echo '<div class="row">
        <div class="col-md-12">';
    echo $footnote;
    echo '</div></div>';
} else if (!empty($custommenu)) {
    echo '<div class="row">
        <div class="col-md-12">';
    echo $custommenu;
    echo '</div></div>';
}

/* Social media links */
$socialmedialinks = '';
if (!empty($PAGE->theme->settings->facebook)) {
    $socialmedialinks .= $this->social_menu_link('facebook', $PAGE->theme->settings->facebook);
}
if (!empty($PAGE->theme->settings->twitter)) {
    $socialmedialinks .= $this->social_menu_link('twitter', $PAGE->theme->settings->twitter);
}
if (!empty($PAGE->theme->settings->youtube)) {
    $socialmedialinks .= $this->social_menu_link('youtube', $PAGE->theme->settings->youtube);
}
if (!empty($PAGE->theme->settings->instagram)) {
    $socialmedialinks .= $this->social_menu_link('instagram', $PAGE->theme->settings->instagram);
}
if(!empty($socialmedialinks)) {
    echo '<div id="snap_socialmedia_links">'.$socialmedialinks.'</div>';
}
?>

<div id='mrooms-footer' class="helplink text-right">
    <small>
    <?php
    if ($OUTPUT->page_doc_link()) {
        echo $OUTPUT->page_doc_link();
    }
    $poweredbyrunby = get_string('poweredbyrunby', 'theme_snap');
    ?>
    <br/><?php echo $poweredbyrunby ?>
    </small>
</div>
<!-- close mrooms footer -->
<div id="page-footer">
<?php echo $OUTPUT->lang_menu(); ?>
<?php echo $OUTPUT->standard_footer_html(); ?>
</div>
</footer>
<?php echo $OUTPUT->standard_end_of_body_html() ?>
<!-- bye! -->
</body>
</html>
