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
 * Block block_course_recommender
 * This block allows users to select their interests from a list of tags.
 * and recommends courses based on those interests.
 *
 * @package    block_course_recommender
 * @copyright  2025 Sadik Mert
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_course_recommender extends block_base {
    /**
     * Initializes the block title with the plugin name.
     *
     * This method is called when the block is initialized and sets
     * the block's title using the localized plugin name.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_course_recommender');
    }

    /**
     * Get the course image URL
     *
     * @param stdClass $course
     * @return string image url
     */
    protected function get_course_image_url($course) {
        global $OUTPUT;

        return $OUTPUT->get_generated_image_for_id($course->id);
    }

    /**
     * Returns the content of the block.
     *
     * This method generates the HTML content displayed within the block.
     * If the content has already been generated, the cached version is returned.
     *
     * @return stdClass The block content object.
     */
    public function get_content() {
        global $OUTPUT, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        // Setup the AMD module.
        $this->page->requires->js_call_amd('block_course_recommender/recommender', 'init');

        $tags = $this->load_tags();
        $interests = $this->extract_tag_names($tags);

        if (empty($interests)) {
            $this->content->text .= html_writer::tag('p', get_string('notagsfound', 'block_course_recommender'));
            return $this->content;
        }

        $related = [];
        $knowncourseids = [];
        if (isloggedin() && !isguestuser()) {
            if (get_config('block_course_recommender', 'enrolmentfilter')) {
                $knowncourseids = array_merge($knowncourseids, array_keys(enrol_get_users_courses($USER->id, true)));
            }
            if (\block_course_recommender\completion_lookup::is_enabled()) {
                $knowncourseids = array_merge(
                    $knowncourseids,
                    \block_course_recommender\completion_lookup::get_completed_course_ids($USER->id)
                );
            }
        }
        if (!empty($knowncourseids)) {
            $knowncourseids = array_values(array_unique($knowncourseids));
            $knowntagnames = $this->get_enrolled_course_tagnames($knowncourseids);
            [$interests, $related] = $this->filter_and_boost_tags($interests, $knowntagnames, $knowncourseids);
        }

        $selected = [];
        if (!empty($_POST)) {
            $selected = optional_param_array('interests', [], PARAM_RAW);
        } else if (\block_course_recommender\interest_store::is_enabled() && !isguestuser()) {
            $selected = \block_course_recommender\interest_store::get_tagnames($USER->id);
        }

        $tagsdata = $this->prepare_tagsdata($interests, $selected, $related);
        $data = $this->prepare_template_data($tagsdata, $interests, $selected);

        $this->content->text .= $OUTPUT->render_from_template('block_course_recommender/tagform', $data);
        $this->content->text .= html_writer::tag('button', get_string('expandresults', 'block_course_recommender'), [
            'type' => 'button',
            'class' => 'btn btn-link courserecommender-expand',
            'data-action' => 'expand',
        ]);
        $this->content->text .= html_writer::start_div('courserecommender-results') . html_writer::end_div();
        return $this->content;
    }

    /**
     * Loads and sorts tags, using cache if available.
     *
     * @return array
     */
    protected function load_tags() {
        global $DB;
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, 'block_course_recommender', 'tags');
        $tags = $cache->get('alltags');
        if ($tags === false) {
            $tagsort = get_config('block_course_recommender', 'tagsort');
            if ($tagsort === 'az') {
                $orderby = 't.name ASC';
            } else if ($tagsort === 'za') {
                $orderby = 't.name DESC';
            } else {
                $orderby = 'tagcount DESC, t.name ASC';
            }
            $sql = "
                SELECT t.id, t.name, t.rawname, COUNT(ti.id) AS tagcount
                FROM {tag} t
                JOIN {tag_instance} ti ON ti.tagid = t.id
                WHERE ti.itemtype = 'course' AND ti.component = 'core'
                GROUP BY t.id, t.name, t.rawname
                ORDER BY $orderby
            ";
            $tags = $DB->get_records_sql($sql);
            $cache->set('alltags', $tags);
        }
        return $tags;
    }

    /**
     * Extracts tag names from tag objects.
     *
     * @param array $tags
     * @return array
     */
    protected function extract_tag_names($tags) {
        $interests = [];
        foreach ($tags as $tag) {
            $interests[] = $tag->rawname;
        }
        return $interests;
    }

    /**
     * Gets the tag rawnames used on the given set of courses.
     *
     * @param array $courseids
     * @return array Tag rawnames.
     */
    protected function get_enrolled_course_tagnames($courseids) {
        if (empty($courseids)) {
            return [];
        }

        $tagnames = [];
        $itemtags = \core_tag_tag::get_items_tags('core', 'course', $courseids);
        foreach ($itemtags as $coursetags) {
            foreach ($coursetags as $tag) {
                $tagnames[$tag->rawname] = true;
            }
        }
        return array_keys($tagnames);
    }

    /**
     * Filters out tags that would only lead to courses the user is already enrolled in,
     * and moves tags shared with the user's enrolled courses to the front of the list.
     *
     * @param array $interests All available tag rawnames, in their configured sort order.
     * @param array $enrolledtagnames Tag rawnames used on the user's enrolled courses.
     * @param array $enrolledcourseids Course ids the user is actively enrolled in.
     * @return array [filtered interests, related tag rawnames that were boosted]
     */
    protected function filter_and_boost_tags($interests, $enrolledtagnames, $enrolledcourseids) {
        if (empty($enrolledtagnames)) {
            return [$interests, []];
        }

        global $DB;
        $candidates = array_values(array_intersect($interests, $enrolledtagnames));
        if (empty($candidates)) {
            return [$interests, []];
        }

        [$tagsql, $tagparams] = $DB->get_in_or_equal($candidates, SQL_PARAMS_NAMED, 'tag');
        [$coursesql, $courseparams] = $DB->get_in_or_equal($enrolledcourseids, SQL_PARAMS_NAMED, 'course', false);

        $sql = "
            SELECT DISTINCT t.rawname
            FROM {tag} t
            JOIN {tag_instance} ti ON ti.tagid = t.id
            JOIN {course} c ON c.id = ti.itemid AND c.visible = 1
            WHERE ti.itemtype = 'course' AND ti.component = 'core'
                AND t.rawname $tagsql
                AND c.id $coursesql
        ";
        $stillvisible = $DB->get_fieldset_sql($sql, $tagparams + $courseparams);

        $excluded = array_diff($candidates, $stillvisible);
        $related = array_values(array_diff($candidates, $excluded));

        $remaining = array_values(array_diff($interests, $excluded));
        $ordered = array_merge(
            array_values(array_intersect($remaining, $related)),
            array_values(array_diff($remaining, $related))
        );

        return [$ordered, $related];
    }

    /**
     * Prepares tag data for the Mustache template.
     *
     * @param array $interests
     * @param array $selected
     * @param array $related Tag rawnames to flag as related to the user's enrolled courses.
     * @return array
     */
    protected function prepare_tagsdata($interests, $selected, $related = []) {
        $tagsdata = [];
        foreach ($interests as $tagname) {
            $tagsdata[] = [
                'name' => $tagname,
                'checked' => in_array($tagname, $selected),
                'related' => in_array($tagname, $related),
                'id' => 'interest-' . clean_param($tagname, PARAM_ALPHANUMEXT),
            ];
        }
        return $tagsdata;
    }

    /**
     * Prepares the data array for the Mustache template.
     *
     * @param array $tagsdata
     * @param array $interests
     * @param array $selected Pre-selected interest tag names (e.g. from persisted storage).
     * @return array
     */
    protected function prepare_template_data($tagsdata, $interests, $selected = []) {
        global $OUTPUT;
        $tagcolor = get_config('block_course_recommender', 'tagcolor');
        if (empty($tagcolor)) {
            $tagcolor = '#0f6fc5';
        }
        $maxtags = (int)get_config('block_course_recommender', 'maxtags');
        return [
            'interestlabel' => get_string('interest_label', 'block_course_recommender'),
            'interesthelpicon' => $OUTPUT->help_icon('interest_label', 'block_course_recommender'),
            'tags' => $tagsdata,
            'all_tags_json' => json_encode($interests),
            'tagcolor' => $tagcolor,
            'maxtags' => $maxtags,
            'selectedinterestscsv' => implode(',', $selected),
        ];
    }

    /**
     * Indicates whether the block has a configuration page.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }
}
