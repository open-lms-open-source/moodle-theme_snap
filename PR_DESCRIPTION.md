# Fix: Advanced Feeds localStorage cache invalidation for deadlines

## Problem

The Angular-based Advanced Feeds component caches feed results in the browser's `localStorage`
under the key prefix `themeSnapCachedPMFeedResults`, with a global TTL (default 30 minutes).

When a teacher modifies activity dates (e.g. via `report/editdates`), the backend MUC cache
(`theme_snap/activity_deadlines`) can be purged and rebuilt — but the frontend `localStorage`
cache has no way to detect this. Students and teachers continue to see stale deadline data until
the 30-minute TTL expires naturally or they manually clear browser storage.

## Root Cause

`FeedService` (`vendorjs/snap-custom-elements/src/app/feed.service.ts`) stores the full response
in `localStorage` with only a `timeCreated` timestamp. There is no mechanism to communicate
server-side cache invalidation to the browser.

## Fix

### Backend — `cacheVersion` token in the deadlines feed response

The server-side MUC cache for deadlines (`theme_snap/activity_deadlines`) already maintains an
internal `timestamp` field that is updated every time fresh data is fetched from the database.
This timestamp acts as a natural version token.

**`classes/local.php`** — `deadlines_data()` now reads this timestamp from the `$eventsobj` and
includes it as a `cacheVersion` field on every item in the deadlines feed response.

**`classes/webservice/ws_feed.php`** — The `service_returns()` definition is extended with an
optional `cacheVersion` field (`VALUE_OPTIONAL`) so the external API contract is backward-compatible.

### Frontend — Version-aware cache reads in `FeedService`

**`src/app/feed-item.ts`** — Added optional `cacheVersion?: string` property.

**`src/app/cached-moodle-res.ts`** — Added optional `cacheVersion?: string` property so the
version is persisted alongside the cached data.

**`src/app/feed.service.ts`** — The core change:

1. When storing a response in `localStorage`, the `cacheVersion` from the first feed item is
   saved alongside the data.
2. When a locally-cached result is found and the cached entry carries a `cacheVersion` (i.e. it
   is a versioned feed), `getFeed` **still makes a server request** to compare versions:
   - If the server returns the **same version**, the locally-cached result is returned and its
     `timeCreated` timestamp is refreshed to extend the effective TTL.
   - If the server returns a **different version**, the fresh server response is stored and
     returned, ensuring the user sees up-to-date deadlines immediately.
3. Feeds **without** a `cacheVersion` in their responses (messages, graded, grading, forum posts)
   are completely unaffected — they continue to use the existing TTL-only path.

### Compiled output

The Angular source was rebuilt and packaged into `vendorjs/snap-custom-elements/snap-ce.js`
using the existing `npm run build && npm run package` pipeline.

## Behaviour summary

| Scenario | Before | After |
|---|---|---|
| Teacher edits activity dates; student opens personal menu within 30 min | Stale deadlines shown | Fresh deadlines fetched and displayed |
| No server-side changes; student opens personal menu within 30 min | Cached deadlines shown (fast) | Version matches → cached deadlines returned (fast) |
| Cache TTL expires normally | Fresh fetch | Fresh fetch (unchanged) |
| Non-deadline feeds (messages, graded, etc.) | Existing TTL behaviour | Unchanged — no version token emitted |

## Testing Instructions

### Manual

1. Log in as a student enrolled in a course with upcoming assignment deadlines.
2. Open the Snap personal menu — note the deadline dates shown.
3. Log in as a teacher and change the due date of one of those assignments
   (e.g. via **Reports → Edit dates** or the activity settings).
4. Back in the student session, open the personal menu again **without** waiting for the 30-minute
   TTL to expire (or clearing `localStorage`).
5. **Expected (after fix):** The updated deadline date is displayed immediately.
6. **Before this fix:** The old date would still be shown until the TTL expired.

### Automated

Run the existing Angular unit tests:

```bash
cd vendorjs/snap-custom-elements
npm install
npm run test-headless
```

### Verify backend external API

Run the Moodle PHPUnit suite for the feed webservice:

```bash
vendor/bin/phpunit --filter ws_feed classes/webservice/ws_feed.php
```

Or the full theme_snap suite:

```bash
vendor/bin/phpunit --testsuite theme_snap
```
