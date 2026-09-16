Course Recommender Block - User Documentation

What it does

Block shows tag badges built from all course tags on site. User picks interests (tags), block AJAX-fetches matching courses and lists them below form. No selection = shows popular courses (by active enrolments).

Adding block to a page

1. Turn editing on.
2. "Add a block" -> "Course Recommender".
3. Needs block/course_recommender:addinstance (editingteacher, manager) or myaddinstance (any user, for My Home).

Using it

- Tags render as clickable badges, most popular first (or A-Z/Z-A per admin setting).
- Click badge to toggle selected (highlighted). Selection auto-submits after 300ms debounce, no page reload.
- Search box filters visible badges by typed text (case-insensitive substring match).
- Results area updates below: course card per match - image, title, category, summary (120 chars, tags stripped), participant count, matching tags.
- No tags on site: block shows "No tags available for courses."
- Interests picked but none match any course: "No matching courses found."
- Guest users blocked from fetching results (noguest exception).

Admin settings (Site administration -> Plugins -> Blocks -> Course Recommender)

- Tag Color: badge highlight color (color picker). Default #0073e6.
- Maximum number of tags: caps badges shown initially; 0 = no limit. Search still limited to 20 matches (or maxtags value) at once. Default 0.
- Tag Sorting: popularity (course-count desc), az, or za. Default popularity.

Tag list cached app-wide (block_course_recommender/tags cache) - cache purge needed to see brand-new tags immediately (or falls off on next cache miss).

Data / privacy

Plugin stores no personal data (classes/privacy/provider.php reports null / no-data provider). Only reads existing core tag, tag_instance, course, enrol, user_enrolments tables.

Permissions

- block/course_recommender:view - block context - user: allow, guest: prevent.
- block/course_recommender:addinstance - block context - editingteacher, manager.
- block/course_recommender:myaddinstance - system context - user.

Version

- Component: block_course_recommender
- Release 1.3.1 (build 2026082401)
- Requires Moodle 2022041900+ (4.0+)
