# Member Engagement & Gamification

**STATUS: NOT IMPLEMENTED** — All features below are future work. None have been started.

## Overview

A set of features to drive member engagement with GOPS using gamification, personal stats, milestones, and automated recognition emails. Every feature derives from existing data in the `flights` and `members` tables — no new data entry required from users.

**Core philosophy:** Make logging into GOPS rewarding, not just transactional. Celebrate flying achievements automatically. Give members reasons to visit between flying days.

---

## Goals

1. **Retention** — Give members reasons to log into GOPS regularly
2. **Recognition** — Celebrate milestones (first solo, 100 flights, etc.) automatically
3. **Social** — Leaderboards and achievements create friendly competition
4. **Re-engagement** — Emails and "welcome back" modals bring dormant members back
5. **Word of mouth** — Shareable achievements and stats spread awareness of GOPS

---

## Feature Set

### Leaderboards

Monthly and all-time rankings on a dedicated page or home widget:

| Category | Period | Calculation |
|----------|--------|-------------|
| Most Flights | Month / Season / All-time | COUNT of flights where `pic = memberid` |
| Most Hours | Month / Season / All-time | SUM of `(land - start) / 3600000` where `pic = memberid` |
| Tow Pilot Hours | Month / All-time | SUM of hours where `towpilot = memberid` |

**Note:** Currently only tracks the PIC's hours. P2 time could be added as a separate leaderboard later.

**UI:**
```
+---------------------------------------------------+
| Leaderboards          [This Month v]              |
|                                                    |
|  #1  Fred Gordon          34 flights     28.5h    |
|  #2  Jane Doe             28 flights     22.1h    |
|  #3  Bob Smith            22 flights     18.7h    |
|  ..  You                  15 flights     12.0h    |
|                                                    |
|  [Flights] [Hours] [Tow Pilots]                    |
+---------------------------------------------------+
```

If the current member is not in the top 10, show a trailing row with their position and a delta: "You're #23 — 4 flights behind #10".

### Streaks

Tracks consecutive flying periods, displayed on the profile and home page:

| Streak Type | Definition | Display |
|-------------|------------|---------|
| Monthly Streak | Consecutive calendar months with >= 1 flight | "12 months running!" |
| Season Streak | Consecutive seasons (summer/winter) with >= 1 flight | "3 seasons straight" |
| Year Streak | Consecutive calendar years with >= 1 flight | "7 years and counting!" |

**Calculation query:**
```sql
-- Count distinct months with flights for a member
SELECT COUNT(DISTINCT CONCAT(YEAR(FROM_UNIXTIME(start/1000)), '-', MONTH(FROM_UNIXTIME(start/1000))))
FROM flights
WHERE pic = $memberId AND deleted = 0 AND type = 1
```

**Breaking a streak:** When 60 days pass without a flight, streak resets to 0.

**UI:**
```
+---------------------------------------------------+
|  Your Streaks                                      |
|                                                    |
|  [FIRE ICON] 12 months  (Feb 2025 - Jan 2026)     |
|  [FIRE ICON] 3 seasons  (Summer 2024 - Summer 26) |
|  [TREE ICON] 7 straight years                     |
+---------------------------------------------------+
```

### Achievements / Badges

Permanent badges earned by reaching milestones. Displayed on member profile and home page.

**Category 1: Flight Count Milestones**

| Badge | Requirement | Notes |
|-------|-------------|-------|
| First Flight | 1 flight logged | Given to all new members automatically |
| Wingman | 10 flights | |
| Century Club | 100 flights | |
| Double Century | 200 flights | |
| Soaring Legend | 500 flights | |
| GOPS God | 1000 flights | |

**Category 2: Hours Milestones**

| Badge | Requirement |
|-------|-------------|
| First Hour | 1 hour PIC |
| Kilohour | 100 hours PIC |
| Marathon Pilot | 500 hours PIC |
| Sky King | 1000 hours PIC |

**Category 3: Altitude Milestones**

| Badge | Requirement |
|-------|-------------|
| Bronze Altitude | First flight above 5000ft |
| Silver Altitude | First flight above 10000ft |
| Gold Altitude | First flight above 15000ft |
| Diamond Altitude | First flight above 20000ft |

**Category 4: Achievement Milestones**

| Badge | Requirement | Detection |
|-------|-------------|-----------|
| First Solo | Member has `gone_solo = 1` | `members.gone_solo` column |
| All-Rounder | Flew 5+ different gliders | DISTINCT `glider` in flights table |
| Tow Pilot | Flew as towpilot 50+ times | COUNT of `towpilot` flights |
| Variety Pack | Flew with 10+ different PICs | DISTINCT `pic` in flights where member is p2 |
| Early Bird | 10+ flights starting before 10am | `start` time filter |
| Night Hawk | 10+ flights in a single month | COUNT by month |
| Cross Country | First flight over 50km from home | Requires GPS track distance (future) |
| Instructor | Flew as PIC with 5+ different students | Membership class or role check |

**Calculation:**
```sql
-- Altitude badge: check if member ever exceeded a threshold
SELECT MAX(height) FROM flights WHERE pic = $memberId AND deleted = 0

-- Variety badge
SELECT COUNT(DISTINCT glider) FROM flights WHERE pic = $memberId AND deleted = 0
```

### Milestone Auto-Celebration

When a member hits a milestone (e.g. 100th flight), a congratulatory modal appears on their next page load:

```
+------------------------------------------+
|  CONGRATULATIONS! 🎉                     |
|                                          |
|  You've just reached 100 flights!        |
|                                          |
|  You're now a Century Club member.       |
|  Only 14 other members have hit this.    |
|                                          |
|  [View Badge]  [Share]  [Dismiss]        |
+------------------------------------------+
```

**Detection:** On any page load, compare current flight count against stored badge thresholds. If a threshold was crossed since last visit, show the modal.

**Storage:** A `member_milestones` table tracks which milestones have been acknowledged:

```sql
CREATE TABLE member_milestones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  milestone_key VARCHAR(32) NOT NULL,   -- e.g. 'flights_100', 'hours_100', 'first_solo'
  achieved_at DATETIME NOT NULL,
  acknowledged TINYINT(1) DEFAULT 0,     -- dismissed by user
  email_sent TINYINT(1) DEFAULT 0,
  FOREIGN KEY (member_id) REFERENCES members(id),
  UNIQUE KEY (member_id, milestone_key)
);
```

### Welcome Back Modal

When a member hasn't flown in 30+ days, show a re-engagement modal on the home page:

```
+------------------------------------------+
|  Welcome back!                           |
|                                          |
|  You haven't flown since Feb 14.         |
|                                          |
|  While you were away:                    |
|  - 3 new badges were added              |
|  - 47 flights logged by the club        |
|  - Your monthly streak was broken       |
|                                          |
|  [Check My Flights]  [Leaderboards]      |
+------------------------------------------+
```

**Detection:** On home page load, check `MAX(f.start)` for the member. If > 30 days ago, show modal. Track dismissals in a session flag to prevent repeated showing.

**"What you missed" section** queries:
- New badges earned by other members (or new badge types created)
- Total club flights since their last flight
- Their streak status

### Happy Birthday

On the member's birthday, show a banner on the home page:

```
+------------------------------------------+
|  Happy Birthday, Fred! 🎂               |
|                                          |
|  What better way to celebrate than       |
|  a flight?                               |
+------------------------------------------+
```

**Detection:** `members.date_of_birth` compared to today. Show once per day (session flag or cookie).

Trivial to implement — one SQL field check.

### First Solo Shoutout (Email)

When a member's `gone_solo` flag is set to 1, send a congratulatory email:

```
Subject: Congratulations on your first solo, Fred! 🎉

Hi Fred,

GOPS noticed you went solo recently — that's a huge milestone!
We hope you're feeling proud.

Your first solo flight:
  Date: 15 May 2026
  Glider: GMB
  Instructor: Jane Doe

See your full flying history at:
  https://gops.wwgc.co.nz/MyFlights

— The GOPS Team
```

**Triggers:** Check on each page load or via a daily cron. Query: `SELECT * FROM members WHERE gone_solo = 1 AND id NOT IN (SELECT member_id FROM member_milestones WHERE milestone_key = 'first_solo' AND email_sent = 1)`.

**Also credits the instructor:**
```
Instructor recognition:
  You signed off Fred Gordon for their first solo on 15 May 2026.
```

### Soaring Stats Mystery Box

On the home page or MyFlights, a small card that shows a random personal stat on each page load:

```
+----------------------------------+
|  Did you know?                   |
|                                  |
|  Your longest flight was         |
|  1h 23m on Nov 15, 2025.        |
+----------------------------------+

+----------------------------------+
|  Did you know?                   |
|                                  |
|  You've flown with 27            |
|  different PICs. Nice!          |
+----------------------------------+

+----------------------------------+
|  Did you know?                   |
|                                  |
|  You average 42 min per flight.  |
+----------------------------------+
```

**Stat pool (all from existing data):**

| Stat | Query |
|------|-------|
| Longest flight duration | `MAX(land - start)` |
| Total flights | `COUNT(*)` |
| Total hours | `SUM(land - start) / 3600000` |
| Favorite glider | Mode of `glider` |
| Most common PIC | Mode of `pic` |
| Distinct gliders flown | `COUNT(DISTINCT glider)` |
| Distinct PICs flown with | `COUNT(DISTINCT pic)` |
| Average flight duration | `AVG(land - start)` |
| Earliest flight | `MIN(start)` |
| Latest flight | `MAX(start)` |
| First flight date | `MIN(localdate)` |
| Glider with most hours | `SUM per glider, top 1` |
| Month with most flights | `COUNT per month, top 1` |
| Busiest month ever | Max flights in any month |
| Altitude PR | `MAX(height)` |

Display one at random on page load. Rotate daily (session flag to avoid changing every refresh).

### Time Capsule

Show what the member was doing on this day in past years:

```
+------------------------------------------+
|  On This Day                              |
|                                          |
|  One year ago (15 May 2025):             |
|  You flew GMB with Fred Gordon           |
|  to 4500ft for 35 minutes.              |
|                                          |
|  Two years ago (15 May 2024):            |
|  You flew GNB solo for 52 minutes.      |
+------------------------------------------+
```

**Query:**
```sql
SELECT * FROM flights
WHERE pic = $memberId AND deleted = 0
  AND DATE_FORMAT(FROM_UNIXTIME(start/1000), '%m-%d') = DATE_FORMAT(NOW(), '%m-%d')
  AND YEAR(FROM_UNIXTIME(start/1000)) < YEAR(NOW())
ORDER BY localdate DESC
```

If no flights on this exact date, show next closest: "On 14 May 2024, you flew..."

### Monthly Bingo Card

A 3x3 or 4x4 grid of challenges for the month. Completing challenges fills squares.

**Challenge pool:**

| Challenge | Detection |
|-----------|-----------|
| Fly 3+ gliders this month | DISTINCT glider count |
| 1 winch launch | launchtype = 2 |
| Flight over 5000ft | height >= 5000 |
| Flight before 10am | start time before 10:00 |
| Fly with 2+ different PICs | DISTINCT pic |
| Fly solo | p2 = NULL or 0 |
| Stay up for 1h+ | land - start > 3600000 |
| Fly at a different location | DISTINCT location |
| Fly on a weekend | DAYOFWEEK(localdate) |
| 5+ flights in a month | COUNT(*) |
| Night flight after 7pm | start after 19:00 |
| Fly the same glider twice | COUNT same glider >= 2 |

**UI:**
```
+---------------------------------------------------+
| January Bingo Card             3/9 complete       |
|                                                    |
| +----------+----------+----------+                |
| | Fly 3    | Winch     | Flight   |               |
| | gliders  | launch    | >5000ft  |               |
| |    [NO]  |   [YES]   |   [YES]  |               |
| +----------+----------+----------+                |
| | Before   | Fly with  | Fly solo |                |
| | 10am     | 2 PICs    |          |                |
| |    [NO]  |   [YES]   |   [NO]   |               |
| +----------+----------+----------+                |
| | 1h+      | Different | 5 flights |               |
| | flight   | location  |          |                |
| |    [YES] |   [NO]    |   [YES]  |               |
| +----------+----------+----------+                |
+---------------------------------------------------+
```

Completed rows/columns/diagonals = extra badge.

**Storage:**
```sql
CREATE TABLE bingo_cards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  year_month INT NOT NULL,         -- e.g. 202601 for Jan 2026
  challenges JSON NOT NULL,        -- list of challenge keys with completed flag
  FOREIGN KEY (member_id) REFERENCES members(id),
  UNIQUE KEY (member_id, year_month)
);
```

---

## Phased Implementation Plan

### Phase 1: Quick Wins (High Impact, Low Build)

| Feature | Est. Effort | Why First |
|---------|-------------|-----------|
| Happy Birthday banner | 1-2 hours | Trivial, cute, visible |
| First Solo shoutout email | 4-6 hours | Meaningful, drives logins |
| Soaring Stats Mystery Box | 4-6 hours | Fun, requires only SQL |
| Welcome Back modal | 4-6 hours | Retention win |

### Phase 2: Social & Competition

| Feature | Est. Effort | Why Second |
|---------|-------------|-----------|
| Leaderboards (flights + hours) | 8-12 hours | Needs new page + caching |
| Streaks | 6-8 hours | Needs streak calc logic |
| Achievements/Badges | 12-16 hours | Needs DB table + detection engine |

### Phase 3: Advanced Content

| Feature | Est. Effort | Notes |
|---------|-------------|-------|
| Time Capsule | 6-8 hours | Historical queries, needs nice UI |
| Monthly Bingo Card | 12-16 hours | Challenge engine + card UI |
| Milestone modals | 8-12 hours | Needs detection + dismissal tracking |

---

## Database Changes

### New Tables

```sql
CREATE TABLE member_milestones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  milestone_key VARCHAR(32) NOT NULL,
  achieved_at DATETIME NOT NULL,
  acknowledged TINYINT(1) DEFAULT 0,
  email_sent TINYINT(1) DEFAULT 0,
  FOREIGN KEY (member_id) REFERENCES members(id),
  UNIQUE KEY (member_id, milestone_key)
);

CREATE TABLE bingo_cards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  year_month INT NOT NULL,
  challenges JSON NOT NULL,
  FOREIGN KEY (member_id) REFERENCES members(id),
  UNIQUE KEY (member_id, year_month)
);
```

### Schema Changes via Laravel Migration

```bash
cd lrv
vagrant ssh -c "cd /home/vagrant/code/lrv && php artisan make:migration create_member_milestones_table"
vagrant ssh -c "cd /home/vagrant/code/lrv && php artisan make:migration create_bingo_cards_table"
```

---

## Files to Create

| File | Purpose |
|------|---------|
| `leaderboards.php` | Leaderboard page with monthly/seasonal/all-time views |
| `api/leaderboard-data.php` | API endpoint for leaderboard JSON |
| `helpers/achievements.php` | Achievement detection and badge logic |
| `helpers/streaks.php` | Streak calculation functions |
| `cron/check-milestones.php` | Daily cron: detect new milestones, send shoutout emails |
| `lrv/database/migrations/..._create_member_milestones_table.php` | Milestones table |
| `lrv/database/migrations/..._create_bingo_cards_table.php` | Bingo cards table |

## Files to Modify

| File | Change |
|------|--------|
| `home.php` | Add Happy Birthday banner, Welcome Back modal, Mystery Box card, streak display, achievement badges |
| `MyFlights.php` | Add Time Capsule section, achievement showcase |
| `helpers/mail.php` | Add milestone email template method |
| `docs/CRONS.md` | Add daily milestone cron entry |

---

## Email Templates

### First Solo

```
Subject: Congratulations on your first solo, {name}! 

Hi {name},

GOPS noticed you went solo recently — that's a huge milestone!

  First solo flight: {date} in {glider}
  Instructor: {instructor_name}

See your full flying history at:
  https://gops.wwgc.co.nz/MyFlights

— The GOPS Team
https://gops.wwgc.co.nz
```

### Instructor Credit (sent alongside first solo)

```
Subject: You signed off {student_name} for their first solo!

Hi {name},

{student_name} completed their first solo flight on {date}.
Great work mentoring the next generation of pilots!

See your instructor stats:
  https://gops.wwgc.co.nz/MyFlights

— The GOPS Team
```

### Milestone Reached

```
Subject: You just reached {milestone} on GOPS!

Hi {name},

Congratulations — you've reached {milestone}!

  Badge: {badge_name}
  Stats: {context} (e.g. "You're one of 14 members at this level")

View your achievements:
  https://gops.wwgc.co.nz/achievements

— The GOPS Team
```

---

## Questions and Decisions

**Q:** Should leaderboards be real-time or cached (e.g. updated hourly/daily by cron)?
**Q:** Achievements — retroactive (scan all historical flights) or forward-only (detect from today)?
**Q:** Streak grace period — does a member get 30 days or 60 days before their streak breaks?
**Q:** Bingo cards — same card for everyone, or randomly generated per member?
**Q:** Email sending — use existing `Mail` class, or set up SMTP for deliverability before launching milestone emails?

## Rejected Ideas

- **Fake points / XP system** — Meaningless without a redeem mechanic. Real stats (flights, hours) are more compelling.
- **Loot boxes** — Artificial. The mystery box shows real personal stats, not random rewards.
- **Competition prizes** — Introduces treasury complexity. Leaderboards are reward enough.
- **Gated features** — "Unlock" access by reaching milestones. Creates frustration, not engagement.
- **Push notifications** — Members haven't opted in. Email is the right channel.
