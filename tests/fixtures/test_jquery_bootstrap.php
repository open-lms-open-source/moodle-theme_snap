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
 * Create a page that can be used for testing the bootstrap jquery plugin and that it does not overwrite jquery.
 * @author    Guy Thomas <gthomas@moodlerooms.com>
 * @copyright Copyright (c) 2017 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../../config.php');

defined('MOODLE_INTERNAL') || die();

global $PAGE, $OUTPUT;

$PAGE->set_url(new moodle_url('/mod/collaborate/tests/fixtures/test_jquery_bootstrap.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Bootstrap jquery plugin test');
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');

echo $OUTPUT->header();

$OUTPUT->heading ('Bootstrap jquery plugin test');

$output = <<<EOF
<h2>Test non-bootstrap jquery plugin (jquery ui)</h2>

<div id="progressbar"><div class="progress-label">Loading...</div></div>
<script>
$(function() {
    $( "#progressbar" ).progressbar({
            value: false
        });
    });    
</script>

<h2>Test bootstrap jquery plugin</h2>

<!-- Button trigger modal -->
<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#myModal">
  Launch demo modal
</button>

<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Modal title</h4>
      </div>
      <div class="modal-body">
        Hey there buddy!
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>
EOF;

echo $output;

echo $OUTPUT->footer();
