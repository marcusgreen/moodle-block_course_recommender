## Course Recommender Block - Placement Guide

How an administrator can place the block on different pages, and which settings to use for each
combination.

The block does not override `applicable_formats()`, so it inherits the core default
`['all' => true, 'mod' => false, 'tag' => false]`. It can go on the Dashboard, the user profile
page, Site home and course pages. It cannot go on activity (mod) pages or tag pages. The block does
not depend on a course context, so it behaves the same wherever it is placed.

Capabilities involved (`db/access.php`):

- `block/course_recommender:addinstance` - editingteacher, manager. Needed to add the block to a
  course or Site home.
- `block/course_recommender:myaddinstance` - authenticated user. Needed to add the block to a
  Dashboard or profile page.

Guests and users who are not logged in do not get personalised content (checked in
`block_course_recommender::get_content()`).

### 1. Every user's Dashboard (`/my/`)

1. Site administration > Appearance > Default Dashboard page (`/my/indexsys.php`).
2. Turn editing on and add "Course Recommender".
3. Click "Reset Dashboard for all users".

Without the reset, only users who have never customised their Dashboard see the block. The reset
discards every user's own Dashboard customisations.

Alternative: tick "Force users to use the default dashboard" (`forcedefaultmymoodle`, in
Site administration > Appearance > Navigation). Every user then sees the default page and no reset
is needed, but users can no longer customise their Dashboard.

### 2. Every user's profile page (`/user/profile.php`)

1. Site administration > Appearance > Default profile page (`/user/profilesys.php`).
2. Turn editing on and add "Course Recommender".
3. Click "Reset profile for all users".

As with the Dashboard, the reset discards users' own profile page customisations, and without it
only users with an uncustomised profile see the block.

Adding the block from a single user's own profile page only affects that user's profile.

### 3. Every course page, existing and new (sticky block)

1. Go to Site home and turn editing on.
2. Add "Course Recommender".
3. Configure the block and set "Page contexts" to "Display throughout the entire site".
4. Open any course, turn editing on, configure the same block and set "Display on page types" to
   "Any course page".

There is one block instance, held in the system context, shown on the main page of every course.
Settings for that instance apply everywhere. Because of `'mod' => false`, it does not show on
activity pages.

Teachers cannot delete or configure the block in their course, but they can hide it for their
course. Only administrators can change or remove it.

Choosing "Any page" instead of "Any course page" in step 4 shows it on most pages of the site, not
just courses.

### 4. New courses only (default blocks)

Add to `config.php`:

```php
$CFG->defaultblocks_override = ':course_recommender';
```

Each course created after this change gets its own copy of the block. Existing courses are not
changed. Teachers can configure, move or delete their course's copy.

This overrides the whole default block list for new courses. To keep other defaults, list them too,
for example `':course_recommender,calendar_upcoming'`. The part before the colon is the left region
and the part after it is the right region.

Moodle has no admin UI for adding a block to existing courses in bulk. That would need a CLI or
admin script using `$page->blocks->add_blocks()`.

### 5. A single course or Site home

A teacher or manager turns editing on in the course (or an admin on Site home) and adds
"Course Recommender". This affects that page only.

### Combining placements

The methods above are independent and can be used together. Each one creates its own block
instance.

| Goal | Methods to use |
|---|---|
| Dashboard only | 1 |
| Dashboard and profile | 1 + 2 |
| All courses, locked by the admin | 3 (with "Any course page") |
| All courses, editable by teachers | 4 for new courses. Existing courses need adding one by one (5) or by a script |
| Dashboard and all courses | 1 + 3 |
| Everywhere a user normally lands | 1 + 2 + 3 |
| Selected courses only | 5 in each course |

Avoid combining 3 with 4 or 5 on the same courses. The course then shows two copies of the block:
the sticky one and the course's own one.

### AI features and placement

Placement does not change the AI behaviour. The embedding re-ranker (`embedding_rerank`) and AI
blurbs (`aiblurb`) are site-wide settings, off by default, and apply to every instance of the block
wherever it is placed. With both off, the block matches courses by tag in SQL and makes no external
calls.
