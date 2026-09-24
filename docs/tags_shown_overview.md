# Course Recommender: how it decides which topics to show

## What the plugin does

Course Recommender is a panel ("block") that can be added to pages in Moodle. It shows a set of topic labels, such as "Leadership", "Excel" or "First Aid". A learner clicks the topics they're interested in, and the block lists courses that match them. It helps people find relevant courses without searching the whole catalogue.

The topics come from the **tags** that course administrators already add to courses. The plugin doesn't need a separate list of topics. If a course is tagged "Excel", "Excel" becomes a topic in the block.

## Why different people see different topics

Everyone starts from the same list: every tag used on at least one course. The list is then adjusted for each person.

1. **Topics that lead nowhere new are removed.** Suppose every course tagged "Excel" is one the learner is already enrolled in or has finished. Showing "Excel" would only offer courses they already have, so it's hidden for them.

2. **Topics linked to their current learning come first.** Suppose a learner is taking a course tagged "Leadership", and other Leadership courses exist that they haven't taken. "Leadership" moves to the front of their list and is highlighted, because it's a likely next step.

3. **Their earlier choices are remembered.** If this feature is turned on, the topics a learner picked last time are already selected when they come back.

4. **The list can be kept short.** An administrator can limit how many topics are shown at once. The rest are still available through a search box.

Guests, and learners with no courses yet, see the full list without these personal adjustments.

## Settings administrators control

| Setting (as named on the settings page) | What it changes |
|---|---|
| Tag Sorting | Most-used topics first, or alphabetical order |
| Maximum number of tags | How many topics appear before the learner needs to search |
| Refine tags by your enrolments | Removes topics that would only show courses the learner is enrolled in, and moves related topics to the front |
| Refine using completed courses | Does the same for courses the learner has already finished |
| Remember selected interests | Saves each learner's selected topics for their next visit |
| Tag Color | The colour of the topic labels |

## Points to consider when evaluating

- **The quality of the recommendations depends on course tagging.** Consistent tags on courses give useful topics. Missing or inconsistent tags ("Excel", "MS Excel", "excel skills") give a messy or thin list.
- **Personal data is limited and optional.** The plugin reads existing enrolment and completion records. It stores its own data only when "Remember selected interests" is on, and then only the topics each learner selected. That data is covered by Moodle's standard privacy (GDPR) export and delete tools.
- **Known quirk:** a topic used only on hidden courses can still appear in the list, even though it leads to no courses the learner can see. Keeping tags off hidden courses avoids this.
