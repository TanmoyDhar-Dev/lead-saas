# n8n Supabase Lead Ownership Fix

To ensure that leads scraped by n8n immediately appear in the Laravel frontend for the user who initiated the search, you must update the n8n Lead Scraping workflow. 

Currently, n8n inserts leads with `user_id = null`, and Laravel runs a cron/fallback routine to "claim" them based on search parameters and time. While this works, it can be fragile.

**The Fix:**
Laravel now sends `user_id` and `lead_search_id` in the webhook payload. Update the n8n Postgres/Supabase DB insert node to map these directly:

1. Open your Lead Scraping workflow in n8n.
2. Open the Postgres node that upserts into the `public.leads` table.
3. Add the following column mappings:

```
user_id:
={{ $('Lead Search Trigger').first().json.body.user_id || null }}

source:
n8n_search
```

*(Note: Replace `'Lead Search Trigger'` with the actual name of your starting Webhook node if it is different).*

Once mapped, every lead inserted by n8n will immediately have the correct `user_id`, completely bypassing the need for Laravel to "guess" and claim leads retroactively.
