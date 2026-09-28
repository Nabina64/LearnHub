# LearnHub - New Features

## 1. Setup (important - do this first)

Open phpMyAdmin, select the `online_learning` database, go to the
**SQL** tab and run the file:

```
database/feature_upgrade.sql
```

This adds:

- `users.status` (`pending` / `approved` / `rejected`) and `users.approved_at`
- a new `course_views` table

All existing users are automatically set to `approved`, so nobody
gets locked out.

---

## 2. Teacher approval

| Step | What happens |
|---|---|
| Someone registers as **Teacher** | Account is saved with status `pending` |
| They try to login | Blocked with the message "waiting for admin approval" |
| Admin opens **Teacher Requests** | Sees the pending list, clicks Approve or Reject |
| Teacher logs in again | Works normally |

Students are still approved automatically - nothing changed for them.

Admin pages:

- `admin/teacher-requests.php` - pending list + recently handled list
- `admin/approve-teacher.php` - approve / reject action
- `admin/users.php` - now has a Status column and a quick Approve button
- `admin/dashboard.php` - shows the pending request count

A rejected teacher can be approved later, and an approved teacher can be
revoked, from the same page.

---

## 3. Course view counting

Every time a student opens `student/course-details.php`, a row is added
to `course_views`.

To stop page refresh from inflating the numbers, the same user is
counted only once per hour (see `record_course_view()` in
`includes/functions.php`).

---

## 4. Teacher analytics

`teacher/analytics.php` shows, for the logged-in teacher only:

- Total courses, total views, views this week, total students
- The **most viewed course** highlighted at the top
- A table of every course sorted by views, with:
  - Total views
  - Unique viewers
  - Views in the last 7 days
  - Enrolled students
  - A popularity bar comparing each course to the top one

`teacher/courses/index.php` also shows view and student counts on
each course card.

---

## 5. Teacher's student list

`teacher/students.php` shows all students enrolled in the courses that
this teacher created, with name, email, course and enrollment date.
There is a dropdown to filter by a single course.

A teacher can never see another teacher's students - every query is
filtered by `courses.teacher_id = <logged in teacher>`.

---

## 6. Trash (soft delete) for lessons and users

Nothing gets removed from the database immediately anymore.

**Lessons (teacher side):**

| Action | Where |
|---|---|
| Delete a lesson | `teacher/lessons/index.php` → "Delete" — moves it to trash |
| View trash | `teacher/lessons/index.php` → "🗑 Trash" button, or `teacher/lessons/trash.php` |
| Restore | Trash page → "Restore" |
| Permanently delete | Trash page → "Delete Forever" (asks for confirmation, cannot be undone) |

A trashed lesson disappears from "My Lessons", from the student's
course page, and can no longer be edited, until it is restored.

**Users (admin side):**

| Action | Where |
|---|---|
| Delete a user | `admin/users.php` → "Delete" — moves it to trash |
| View trash | `admin/users.php` → "🗑 Trash" button, or `admin/user-trash.php` |
| Restore | Trash page → "Restore" |
| Permanently delete | Trash page → "Delete Forever" |

A trashed user disappears from the users list, from all dashboard
counts, and **cannot login** ("Invalid email or password") until an
admin restores them.

Both trash pages automatically purge anything older than 30 days,
so trash does not need to be emptied by hand — but Delete Forever is
available any time before that.

Added: `database` column `deleted_at` on both `lessons` and `users`
(part of `database/feature_upgrade.sql` — re-run that file if you
already applied the earlier version).

---

## 7. Trash (soft delete) for courses too

Courses follow the exact same trash rule as lessons and users now.

**Teacher side** (`teacher/courses/`):

| Action | Where |
|---|---|
| Delete a course | `teacher/courses/index.php` → "Delete" — moves it to trash |
| View trash | `teacher/courses/index.php` → "🗑 Trash" button, or `teacher/courses/trash.php` |
| Restore | Trash page → "Restore" |
| Permanently delete | Trash page → "Delete Forever" |

**Admin side** (`admin/`):

| Action | Where |
|---|---|
| Delete a course | `admin/courses.php` → "Delete" — moves it to trash |
| View trash | `admin/courses.php` → "🗑 Trash" button, or `admin/course-trash.php` |
| Restore | Trash page → "Restore" |
| Permanently delete | Trash page → "Delete Forever" |

A trashed course disappears from the student's browse page, from
teacher/admin course lists, and cannot be opened, edited or enrolled
into — but its lessons and quizzes stay attached and come back if the
course is restored. **Delete Forever** removes the course together
with its lessons, quizzes, questions, enrollments and thumbnail file,
since this database has no automatic cascade.

Added: `deleted_at` column on `courses` (also part of
`database/feature_upgrade.sql` — re-run it if you already applied an
earlier version; it is safe to run again, existing courses stay
untouched).

---

## 8. Files changed / added

**Added**

- `database/feature_upgrade.sql`
- `admin/teacher-requests.php`
- `admin/approve-teacher.php`
- `teacher/analytics.php`
- `teacher/students.php`

**Changed**

- `auth/register.php` - teachers saved as pending
- `auth/login.php` - blocks pending / rejected teachers
- `admin/dashboard.php` - pending requests card
- `admin/users.php` - status column + approve button
- `teacher/dashboard.php` - stats + most viewed course + new cards
- `teacher/courses/index.php` - views and students per course
- `student/course-details.php` - records the view
- `includes/functions.php` - `record_course_view()` helper
- `includes/navbar.php` - new menu links

---

## 8. Fix: student pages broken / lessons not showing

**Symptom:** a student could login and open *Profile*, but *Dashboard*,
*Courses*, *Course Details* and *Lessons* gave a blank / 500 page.

**Cause:** those pages read `courses.deleted_at`. If `feature_upgrade.sql`
was only half applied (re-running the old file stops at the first
"duplicate column" error), that column never got created.

**Fix (already in the code):**

- `includes/schema.php` runs from `config/database.php` and adds any
  missing column/table automatically. Nothing is ever deleted.
- `database/feature_upgrade.sql` is now safe to run many times.
- `database/online_learning.sql` (was empty) is now a full schema for a
  brand-new install. Default admin: `admin@learnhub.com` / `Admin@123`
  (change it after first login).

**Lesson videos:** a normal YouTube link cannot be shown inside an
`<iframe>` (YouTube refuses to connect). `get_video_embed()` in
`includes/functions.php` converts YouTube (watch / youtu.be / shorts /
playlist), Vimeo, Google Drive and direct `.mp4/.webm` links into a
playable player. Any other link is shown as a "Watch Video" button.

---

## 9. Admin: full course information

**Problem:** the admin "View" button opened `student/course-details.php`,
a student-only page, so the admin was sent back to the home page.

**Now (the existing look is kept - same card grid, same buttons):**

- `admin/courses.php` - each course card now also shows the status
  (Active, plus a "No lessons" tag when empty), Lessons / Quizzes / Enrolled
  counts, teacher and created date, with **View / Manage / Delete** buttons.
  Trashed courses stay on the Trash page as before.
- `admin/course-view.php?id=..` (new) - teacher, status, created date,
  page views, description, lessons, quizzes (questions, attempts, average
  score) and enrolled students. The Manage section has Delete (to trash) /
  Restore / Delete Forever, and each student has a **Remove** button
  (removes only the enrollment, never the account).
- `admin/remove-enrollment.php` (new) - POST + security token.
- `student/course-details.php` and `student/courses.php`: an admin or teacher
  who opens them is sent to their own course pages instead of the home page.

---

## 10. Pages added from the site map

**Public:** `courses.php` (search + category), `about.php`, `faq.php`,
`contact.php`, `terms.php`, `privacy.php`. Styles: `assets/css/public.css`
(pages load it with `$extraCss = ["public.css"];`).

**Student:** `student/my-courses.php`, `student/quiz-results.php`,
`student/settings.php`.

**Teacher:** `teacher/profile.php`, `teacher/settings.php`.

**Admin:** `admin/enrollments.php`, `admin/reports.php` (+ `export.php` CSV,
`message-action.php`), `admin/settings.php`.

**Settings that really do something** (Admin -> Settings):

- Allow new registrations - off = the Register page shows "closed".
- New teachers need admin approval - off = teachers can log in at once.
- Contact email / phone / address - shown on the Contact page.

**Account settings** (student / teacher / admin) live in
`includes/account.php`: change name + email, change password, and (student /
teacher only) delete my account. Every change asks for the current password.

**Contact messages** are stored in the `contact_messages` table and are read
in Admin -> Reports (mark read / delete). No email is sent.

**Database:** two new tables, `site_settings` and `contact_messages`. They are
created automatically (includes/schema.php); `database/feature_upgrade.sql`
and `database/online_learning.sql` contain them too.

**Menus:** the navbar of every role now follows the site map. "User Trash" was
removed from the admin navbar (it is still on the Users page -> Trash button).

**Not built:** progress tracking inside a course, password reset by email,
sending email from the Contact form. The Terms and Privacy texts are generic
templates - review them before going live.
