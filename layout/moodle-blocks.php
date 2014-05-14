<?php
$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$knownregionpre = $PAGE->blocks->is_known_region('side-pre');

if ($hassidepre) {
    echo '<div id="moodle-blocks" class="clearfix">';
    echo $OUTPUT->blocks('side-pre');
    echo '</div>';
}
