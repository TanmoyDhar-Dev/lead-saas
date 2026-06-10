# Lead Ownership on Duplicate Scrapes

When multiple users scrape the same LinkedIn profile, n8n upserts on `linkedin_url`. Without protection, the latest scrape overwrote `user_id` and `lead_search_id`.

## How it works now

1. **`lead_user` pivot** — Each user–lead pair is stored with the `lead_search_id` that discovered it. Both users see the lead in their respective search results.
2. **PostgreSQL triggers** (production Supabase) — On upsert conflict:
   - Original `user_id` and `lead_search_id` on `leads` are preserved.
   - The scraping user is added to `lead_user` so the lead appears in their search.
3. **Laravel visibility** — `Lead::visibleTo()` and search result queries use owned + shared leads.

No n8n workflow changes are required; existing Postgres upsert nodes continue to work.

## Migrations

- `2026_06_07_000001_create_lead_user_table.php` — pivot + backfill
- `2026_06_07_000002_add_lead_ownership_triggers.php` — PostgreSQL triggers only

Run `php artisan migrate` on each environment using PostgreSQL.
