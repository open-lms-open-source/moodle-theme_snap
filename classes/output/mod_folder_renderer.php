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
 * Snap folder renderer.
 * Overrides core folder renderer.
 *
 * @package   theme_snap
 * @copyright  Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\output;

use html_writer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/folder/renderer.php');

class mod_folder_renderer extends \mod_folder_renderer {
    public function render_folder_tree(\folder_tree $tree) {
        // The folder tree render is overwritten to avoid that 2 folders have the same ID, when lazy load is enabled.
        $treecounter = $tree->context->id;
        $content = '';
        $id = 'folder_tree'. ($treecounter);
        $content .= '<div id="'.$id.'" class="filemanager">';
        $content .= $this->htmllize_tree($tree, array('files' => array(), 'subdirs' => array($tree->dir)));
        $content .= '</div>';

        // Replace the span tag with the H3 tag to avoid a violation in the header structure for accessibility.
        $replace = html_writer::tag('h3', s($tree->folder->name), array('class' => 'fp-filename'));
        $search = html_writer::tag('span', s($tree->folder->name), array('class' => 'fp-filename'));
        $content = str_replace($search, $replace, $content);

        $showexpanded = true;
        if (empty($tree->folder->showexpanded)) {
            $showexpanded = false;
        }
        $this->page->requires->js_init_call('M.mod_folder.init_tree', array($id, $showexpanded));
        return $content;
    }
}
