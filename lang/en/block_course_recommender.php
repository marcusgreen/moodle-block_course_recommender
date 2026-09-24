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
 * English language pack for block_course_recommender
 *
 * @package    block_course_recommender
 * @category   string
 * @copyright  2025 Sadik Mert
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['alltags'] = 'All tags';
$string['cachedef_tags'] = 'Tag List Cache';
$string['course_recommender:addinstance'] = 'Add a new Course Recommender block';
$string['course_recommender:myaddinstance'] = 'Add a new Course Recommender block to My home';
$string['course_recommender:view'] = 'View Course Recommender block';

$string['error'] = 'An error occurred.';

$string['interest_label'] = 'Select your interests';
$string['interest_label_help'] = 'Pick one or more tags that match what you\'re interested in learning. Courses tagged with those topics will be listed below, best matches first. Select or deselect a tag at any time to update the list.';

$string['matchingcourses'] = 'Courses matching your interests';
$string['maxtags'] = 'Maximum number of tags';
$string['maxtags_desc'] = 'Set the maximum number of tags to be displayed for the courses. Enter 0 to display all tags (no limit).';

$string['nocourses'] = 'No matching courses found.';
$string['notagsfound'] = 'No tags available for courses.';
$string['participants'] = '{$a} participants';

$string['pluginname'] = 'Course Recommender';
$string['popularcourses'] = 'Popular courses';
$string['privacy:metadata:core_ai'] = 'When AI recommendation blurbs are enabled, the user\'s selected interest tags and the titles, tags and summaries of recommended courses are sent to the Moodle AI subsystem to generate a short explanation of each recommendation.';
$string['privacy:metadata:course_recommender_interest'] ='Stores a user\'s selected interest tags, when the "Remember selected interests" setting is enabled.';
$string['privacy:metadata:course_recommender_interest:tagid'] = 'The selected interest tag.';
$string['privacy:metadata:course_recommender_interest:timecreated'] = 'The time the interest was selected.';
$string['privacy:metadata:course_recommender_interest:userid'] = 'The ID of the user who selected the interest.';
$string['privacy:metadata:embedding_service'] = 'When similarity re-ranking is enabled, text describing the user\'s interests, completed courses and proficient competencies is sent to the configured embedding service (for example OpenAI, an OpenAI-compatible service or Ollama) to rank recommended courses. No user identifier is sent.';
$string['privacy:metadata:embedding_service:competencies'] = 'The names and descriptions of competencies the user is proficient in, when competency-based personalisation is enabled.';
$string['privacy:metadata:embedding_service:completedcourses'] = 'The names, summaries and tags of courses the user has completed, when completion-based personalisation is enabled.';
$string['privacy:metadata:embedding_service:interests'] = 'The interest tags the user selected.';

$string['err_embeddingerror'] = 'Error retrieving embeddings: {$a}';
$string['err_invalidbackend'] = 'Invalid AI backend configured for the course blurb.';
$string['err_nobaseurl'] = 'No base URL configured for the embedding backend.';
$string['err_noapikey'] = 'No OpenAI API key configured for the embedding backend.';
$string['err_retrievingblurb'] = 'Error retrieving the AI course blurb.';
$string['err_retrievingblurb_checkconfig'] = 'Error retrieving the AI course blurb. Check the AI subsystem configuration.';

$string['expandresults'] = 'View full size';

$string['searchtags'] = 'Search tags';
$string['searchtagshelp'] = 'The most popular tags are shown first. Search to find more existing tags.';
$string['searchtagsplaceholder'] = 'Search existing tags';
$string['settings:aiblurb'] = 'Enable AI "why recommended" blurb';
$string['settings:aiblurb_backend'] = 'AI blurb backend';
$string['settings:aiblurb_backend_core'] = 'Core AI subsystem';
$string['settings:aiblurb_backend_desc'] = 'Which AI backend to use for generating the blurb. Falls back to the core AI subsystem if the selected plugin is not installed.';
$string['settings:aiblurb_backend_local'] = 'Local AI manager (local_ai_manager)';
$string['settings:aiblurb_backend_tool'] = 'AI Connect (tool_aimanager)';
$string['settings:aiblurb_desc'] = 'When enabled, each matching course shows a short AI-generated sentence explaining why it was recommended, based on its title, tags and summary. Only public course data is sent to the AI backend, never student data.';
$string['settings:aiblurb_purpose'] = 'AI blurb purpose';
$string['settings:aiblurb_purpose_desc'] = 'Purpose identifier passed to local_ai_manager for routing/quota. Ignored by other backends.';
$string['settings:embedding_backend'] = 'Embedding backend';
$string['settings:embedding_backend_compatible'] = 'OpenAI-compatible endpoint (LocalAI, LM Studio, vLLM, Azure, gateways...)';
$string['settings:embedding_backend_desc'] = 'Which service generates the embedding vectors used for similarity-based course ranking. Bypasses the core AI subsystem, which has no embedding action.';
$string['settings:embedding_backend_ollama'] = 'Ollama (local or remote)';
$string['settings:embedding_backend_openai'] = 'OpenAI (hosted)';
$string['settings:embedding_baseurl'] = 'Embedding base URL';
$string['settings:embedding_baseurl_desc'] = 'Base URL for the Ollama or OpenAI-compatible backend, e.g. http://localhost:11434 or http://localhost:8080/v1. Ignored by the OpenAI backend.';
$string['settings:embedding_model'] = 'Embedding model';
$string['settings:embedding_model_desc'] = 'Model name to request from the embedding backend, e.g. text-embedding-3-small (OpenAI) or nomic-embed-text (Ollama).';
$string['settings:embedding_openai_apikey'] = 'OpenAI API key';
$string['settings:embedding_openai_apikey_desc'] = 'Required for the OpenAI backend; optional for OpenAI-compatible endpoints that don\'t require auth.';
$string['settings:embedding_rerank'] = 'Enable similarity-based re-ranking';
$string['settings:embedding_rerank_desc'] = 'When enabled, matching courses are re-ordered by semantic similarity between the student\'s selected interests and each course\'s title/tags/summary, instead of exact tag-count only. If "Refine using completed courses" is also enabled, the student\'s completed-course titles/tags/summaries are blended in as a secondary signal (weighted below the selected interests). Requires the embedding backend below to be configured. Course text - including, when applicable, completed-course text - is sent to the embedding backend to build vectors; course vectors are cached, completed-course text is not - see the embedding backend setting for where that data goes.';
$string['settings:completionfilter'] = 'Refine using completed courses';
$string['settings:completionfilter_desc'] = 'When enabled, tags that would only lead to courses the user has already completed are hidden from the interest list, and tags shared with the user\'s completed courses are moved to the top and highlighted - the same treatment as "Refine tags by your enrolments", but based on completion instead of active enrolment (so it still applies after a completed course is unenrolled, e.g. end of term). Already-completed courses are also excluded from the recommended course list. Only considers courses with completion tracking enabled. Uses a live read of the user\'s completion data each time the block loads - nothing is stored.';
$string['settings:competencyfilter'] = 'Refine using competencies';
$string['settings:competencyfilter_desc'] = 'When enabled, the recommended course list is re-ranked using the competencies the user has already achieved (marked proficient) in their learning plans, as a further semantic signal alongside selected interests and completed courses. Requires the "Re-rank by semantic similarity" setting to also be enabled. Uses a live read of the user\'s competency data each time the block loads - nothing is stored.';
$string['settings:embeddingheading'] = 'Embedding provider (for similarity-based recommendations)';
$string['settings:embeddingheading_desc'] = 'Configuration for generating course/interest embeddings, used to rank recommendations by semantic similarity rather than exact tag match.';
$string['settings:enrolmentfilter'] = 'Refine tags by your enrolments';
$string['settings:enrolmentfilter_desc'] = 'When enabled, tags that would only lead to courses the user is already enrolled in are hidden from the interest list, and tags shared with the user\'s current courses are moved to the top and highlighted. Uses a live read of the user\'s enrolments each time the block loads - nothing is stored.';
$string['settings:persistinterests'] = 'Remember selected interests';
$string['settings:persistinterests_desc'] = 'When enabled, a user\'s selected interest tags are saved and pre-selected next time they view the block. Clearing all selections deletes the saved interests. Stores personal data - see privacy metadata.';
$string['tagcolor'] = 'Tag Color';
$string['tagcolor_desc'] = 'Select the color for the tags.';
$string['tagsort'] = 'Tag Sorting';
$string['tagsort_az'] = 'A-Z';
$string['tagsort_desc'] = 'Select the sorting order for the tags.';
$string['tagsort_popularity'] = 'Popularity';
$string['tagsort_za'] = 'Z-A';
