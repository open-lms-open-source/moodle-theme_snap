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
 * Section navigation component.
 *
 * @module     theme_snap/section_navigation
 * @copyright Copyright (c) 2025 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import BaseComponent from 'core_courseformat/local/content';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

export default class SectionNavigation extends BaseComponent {
    static instances = new Map();

    create(descriptor) {
        super.create(descriptor);

        this.sectionId = descriptor.sectionId;
        this.navigationButtons = {
            previous: null,
            next: null,
        };

        this.selectors = {
            SECTION: `.course-content ul.sections > li[data-id="${this.sectionId}"]`,
            PREVIOUS: `.section_footer .previous_section`,
            NEXT: `.section_footer .next_section`,
            TITLE: `.nav_title`,
            ICON: `i[section-number]`,

        };

        this.classes = {
            DISABLED: `disabled`,
        };

        this.attributes = {
            SECTION_NUMBER: `section-number`
        };
    }

    stateReady(state) {
        this.section = state.section.get(this.sectionId);
    }

    static init(target, sectionId) {
        const element = document.querySelector(target);
        if (!element) {
            return null;
        }
        let instance = null;
        if (SectionNavigation.instances.has(sectionId)) {
            instance = SectionNavigation.instances.get(sectionId);
        } else {
            instance = new this({
                element,
                reactive: getCurrentCourseEditor(),
                sectionId: sectionId,
            });
            SectionNavigation.instances.set(sectionId, instance);
        }
        return instance;
    }

    getWatchers() {
        return [
            { watch: `course.sectionlist:updated`, handler: this._sectionNavigationUpdate },
            { watch: `section.cmlist:updated`, handler: this._subsectionNavigationUpdate },
        ];
    }

    /**
     * Handles navigation updates when editing a subsection, triggered when cmlist mutates.
     *
     *  @param {Object} param0 - Object containing the DOM element.
     *  @param {HTMLElement} param0.element - The element representing the current section/subsection.
     * @private
     */
    _subsectionNavigationUpdate({element}) {
        if (!this.reactive.isEditing) {
            return;
        }

        const currentSectionId = this.sectionId;

        const parentsectionid = this.reactive.state.section.get(currentSectionId).parentsectionid;
        if (parentsectionid === null) {
            return;
        }

        const sectionsMap = new Map(
            [...this.reactive.state.section].filter(([, value]) => {
                return value.component !== null && value.parentsectionid == parentsectionid;
            })
        );

        let subsectionMap = this._getCmToSubsectionMapBySectionId(element);
        const sections = element.cmlist
            .filter(cmid => subsectionMap.has(cmid))
            .map(cmid => subsectionMap.get(cmid));

        this._updateNavigation(sections, sectionsMap, currentSectionId);
    }

    /**
     * Handles navigation updates between main course sections (not subsections), triggered when sectionlist mutates.
     *
     * @param {Object} param0 - Object containing the DOM element.
     * @param {HTMLElement} param0.element - The element representing the section list container.
     * @private
     */
    _sectionNavigationUpdate({ element }) {
        if (!this.reactive.isEditing){
            return;
        }
        const currentSectionId = this.sectionId;

        const parentsectionid = this.reactive.state.section.get(currentSectionId).component;
        if (parentsectionid !== null) {
            return;
        }

        const sectionsMap = new Map(
            [...this.reactive.state.section].filter(([, value]) => value.component === null)
        );


        this._updateNavigation(element.sectionlist, sectionsMap, currentSectionId);
    }

    /**
     * Updates the navigation state (previous/next) and triggers button rendering.
     *
     * @param {Array<string>} sections - Ordered list of section or subsection IDs.
     * @param {Map<string, Object>} sectionsMap - Map of section IDs to their section data.
     * @param {string|number} currentSectionId - The currently active section ID.
     *
     * @private
     */
    _updateNavigation(sections, sectionsMap, currentSectionId) {
        const { previous, next } = this._getNextAndPrevious(sections, sectionsMap, currentSectionId);
        this.navigationButtons = { previous, next };
        this.renderNavigationButtons();
    }

    /**
     * Determines the previous and next items based on the current section ID.
     *
     * @param {Array<string>} sections - Ordered array of section or subsection IDs.
     * @param {Map<string, Object>} sectionsMap - Map linking section IDs to section data.
     * @param {string|number} currentSection - The current section ID.
     * @returns {{ previous: Object|null, next: Object|null }} Object containing previous and next section data.
     *
     * @private
     */
    _getNextAndPrevious(sections, sectionsMap, currentSection) {
        const index = sections.indexOf(String(currentSection));
        if (index === -1) {
            return { previous: null, next: null };
        }

        const previousId = index > 0 ? sections[index - 1] : null;
        const nextId = index < sections.length - 1 ? sections[index + 1] : null;

        const previous = previousId && sectionsMap.has(previousId) ? sectionsMap.get(previousId) : null;
        const next = nextId && sectionsMap.has(nextId) ? sectionsMap.get(nextId) : null;

        return { previous, next };
    }

    /**
     * Renders navigation buttons based on the current navigation state.
     * Updates button attributes, section numbers, and titles.
     * Buttons are always present in the DOM but toggled via the "disabled" class.
     *
     * @private
     */
    renderNavigationButtons() {
        const { previous, next } = this.navigationButtons;

        const renderButton = (btnElement, data) => {
            if (!btnElement) {
                return;
            }

            const titleEl = btnElement.querySelector(this.selectors.TITLE);
            const iconEl = btnElement.querySelector(this.selectors.ICON);

            if (data) {
                btnElement.classList.remove(this.classes.DISABLED);
                btnElement.setAttribute(this.attributes.SECTION_NUMBER, data.number);
                btnElement.setAttribute('href', data.sectionurl || '#');

                if (iconEl) {
                    iconEl.setAttribute(this.attributes.SECTION_NUMBER, data.number);
                }
                if (titleEl) {
                    titleEl.textContent = data.title || '';
                    titleEl.setAttribute('aria-label', data.title || '');
                    titleEl.setAttribute('title', data.title || '');
                }
            } else {
                btnElement.classList.add(this.classes.DISABLED);
                btnElement.removeAttribute('href');
                btnElement.setAttribute(this.attributes.SECTION_NUMBER, '');
                if (titleEl) {
                    titleEl.textContent = '';
                    titleEl.removeAttribute('aria-label');
                    titleEl.removeAttribute('title');
                }
            }
        };

        renderButton(this.element.querySelector(this.selectors.PREVIOUS), previous);
        renderButton(this.element.querySelector(this.selectors.NEXT), next);

        const sectionEl = document.querySelector(this.selectors.SECTION);
        if (sectionEl) {
            // The code that displays the sections already loaded does so based on the section number.
            // When sections are moved, this number changes, so this ID must be updated.
            sectionEl.setAttribute('id', `section-${this.section.number}`);
        }
    }

    /**
     * Returns a Map of cmid and subsectionid for a given section.
     *
     * @param {Object} section - The parent section.
     * @returns {Map<string, string>} A Map where keys are cmids and values are subsection ids.
     */
    _getCmToSubsectionMapBySectionId(section) {
        const result = new Map();
        const cmIds = section.cmlist;
        const cmState = this.reactive.state.cm;

        if (!cmIds || !Array.isArray(cmIds)) {
            return result;
        }

        for (const cmid of cmIds) {
            const cm = cmState.get(cmid);

            if (!cm) {
                continue;
            }
            if (cm.delegatesectionid === undefined || cm.delegatesectionid === null) {
                continue;
            }

            result.set(cmid, cm.delegatesectionid);
        }
        return result;
    }
}
