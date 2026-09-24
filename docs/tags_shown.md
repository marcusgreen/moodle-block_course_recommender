# Which tags a user sees

The tag list is built in `block_course_recommender::get_content()` (`block_course_recommender.php`). It goes through five steps, and each one can change which tags a user sees or their order.

## 1. Starting pool: every tag used on at least one course

`load_tags()`

- The query takes every tag with a `tag_instance` where `component = 'core'` and `itemtype = 'course'`.
- The `tagsort` setting sets the order: popularity (how many courses use the tag, the default), A–Z or Z–A.
- The result is cached site-wide as `alltags`, so every user starts from the same list.
- The query doesn't check course visibility, so tags that only appear on hidden courses are included.

## 2. The user's known courses

This step applies only to users who are logged in and aren't guests. The block collects course IDs from two sources, each behind a setting:

- `enrolmentfilter`: the user's active enrolments, via `enrol_get_users_courses($USER->id, true)`.
- `completion_lookup::is_enabled()`: courses the user has completed.

## 3. Filter and boost

`filter_and_boost_tags()`

- It finds the tags used on the known courses (`get_enrolled_course_tagnames()`).
- For each of those tags, it checks whether the tag is also on a **visible course the user doesn't already know**.
  - **If not**, the tag is removed, because clicking it would only lead to courses the user already has.
  - **If so**, the tag is marked as **related** and moved to the front of the list. The sort order from step 1 is kept within the front group and within the rest.
- Related tags get the CSS class `courserecommender-badge-related`, so they look different.

## 4. Pre-selected tags

- After a form POST, the `interests[]` values that were submitted are shown as selected.
- Otherwise, if `persistinterests` is on, the user's saved tags from `interest_store::get_tagnames()` are selected.

## 5. How many are shown

`amd/src/recommender.js`

- If `maxtags` is above 0, only the first `maxtags` badges are shown. Selected tags always stay visible.
- Typing in the search box filters across all tags, showing up to `maxtags` results (20 when `maxtags` is 0).

## Result

For a given user, the visible tags are:

> every course tag, minus tags that only lead to courses they're enrolled in or have completed, with tags shared with their courses moved first, cut to the first `maxtags` in sort order, plus any tags they've selected.

## Edge cases

- **Two users see the same list:** they're guests, or both filter settings are off, or they have no enrolled or completed courses with tags.
- **A tag is missing for one user:** every visible course with that tag is one they're already enrolled in or have completed. It could also be past the `maxtags` cut-off; searching for it will show it.
- **A tag appears but leads to no results:** it's only used on hidden courses (from step 1), or on courses the user can't see.
