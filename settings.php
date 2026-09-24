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
 * Settings for the Course Recommender block
 *
 * @package    block_course_recommender
 * @copyright  2025 Sadik Mert
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcolourpicker(
        'block_course_recommender/tagcolor',
        get_string('tagcolor', 'block_course_recommender'),
        get_string('tagcolor_desc', 'block_course_recommender'),
        '#0073e6'
    ));

    $settings->add(new admin_setting_configtext(
        'block_course_recommender/maxtags',
        get_string('maxtags', 'block_course_recommender'),
        get_string('maxtags_desc', 'block_course_recommender'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configselect(
        'block_course_recommender/tagsort',
        get_string('tagsort', 'block_course_recommender'),
        get_string('tagsort_desc', 'block_course_recommender'),
        'popularity',
        [
            'popularity' => get_string('tagsort_popularity', 'block_course_recommender'),
            'az' => get_string('tagsort_az', 'block_course_recommender'),
            'za' => get_string('tagsort_za', 'block_course_recommender'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_course_recommender/aiblurb',
        get_string('settings:aiblurb', 'block_course_recommender'),
        get_string('settings:aiblurb_desc', 'block_course_recommender'),
        0
    ));

    $settings->add(new admin_setting_configselect(
        'block_course_recommender/aiblurb_backend',
        get_string('settings:aiblurb_backend', 'block_course_recommender'),
        get_string('settings:aiblurb_backend_desc', 'block_course_recommender'),
        'core_ai_subsystem',
        [
            'core_ai_subsystem' => get_string('settings:aiblurb_backend_core', 'block_course_recommender'),
            'local_ai_manager'  => get_string('settings:aiblurb_backend_local', 'block_course_recommender'),
            'tool_aimanager'    => get_string('settings:aiblurb_backend_tool', 'block_course_recommender'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_course_recommender/persistinterests',
        get_string('settings:persistinterests', 'block_course_recommender'),
        get_string('settings:persistinterests_desc', 'block_course_recommender'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_course_recommender/enrolmentfilter',
        get_string('settings:enrolmentfilter', 'block_course_recommender'),
        get_string('settings:enrolmentfilter_desc', 'block_course_recommender'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_course_recommender/completionfilter',
        get_string('settings:completionfilter', 'block_course_recommender'),
        get_string('settings:completionfilter_desc', 'block_course_recommender'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_course_recommender/competencyfilter',
        get_string('settings:competencyfilter', 'block_course_recommender'),
        get_string('settings:competencyfilter_desc', 'block_course_recommender'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'block_course_recommender/aiblurb_purpose',
        get_string('settings:aiblurb_purpose', 'block_course_recommender'),
        get_string('settings:aiblurb_purpose_desc', 'block_course_recommender'),
        'feedback',
        PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_course_recommender/embedding_rerank',
        get_string('settings:embedding_rerank', 'block_course_recommender'),
        get_string('settings:embedding_rerank_desc', 'block_course_recommender'),
        0
    ));

    $settings->add(new admin_setting_heading(
        'block_course_recommender/embeddingheading',
        get_string('settings:embeddingheading', 'block_course_recommender'),
        get_string('settings:embeddingheading_desc', 'block_course_recommender')
    ));

    $settings->add(new admin_setting_configselect(
        'block_course_recommender/embedding_backend',
        get_string('settings:embedding_backend', 'block_course_recommender'),
        get_string('settings:embedding_backend_desc', 'block_course_recommender'),
        'openai',
        [
            'openai'            => get_string('settings:embedding_backend_openai', 'block_course_recommender'),
            'openai_compatible' => get_string('settings:embedding_backend_compatible', 'block_course_recommender'),
            'ollama'            => get_string('settings:embedding_backend_ollama', 'block_course_recommender'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'block_course_recommender/embedding_baseurl',
        get_string('settings:embedding_baseurl', 'block_course_recommender'),
        get_string('settings:embedding_baseurl_desc', 'block_course_recommender'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'block_course_recommender/embedding_openai_apikey',
        get_string('settings:embedding_openai_apikey', 'block_course_recommender'),
        get_string('settings:embedding_openai_apikey_desc', 'block_course_recommender'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'block_course_recommender/embedding_model',
        get_string('settings:embedding_model', 'block_course_recommender'),
        get_string('settings:embedding_model_desc', 'block_course_recommender'),
        'text-embedding-3-small',
        PARAM_RAW_TRIMMED
    ));
}
