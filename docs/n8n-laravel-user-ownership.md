# n8n & Laravel User Ownership Integration

To ensure leads scraped by n8n are correctly assigned to the Laravel user who initiated the search, follow these documentation guidelines.

## Webhook Payload (Laravel to n8n)

When Laravel triggers a lead search, it sends the following JSON payload:

```json
{
  "country": "United States",
  "city": "San Francisco",
  "industry": "Software",
  "position": "CEO",
  "user_id": 123,
  "lead_search_id": "uuid-string-here"
}
```

## n8n Workflow Mapping

In your n8n workflow (e.g., `Lead ScrapV3.5.json`), ensure the **Supabase / Postgres Upsert** node maps the following fields:

| Database Column | n8n Expression |
| --- | --- |
| `user_id` | `{{ $json.user_id }}` |
| `lead_search_id` | `{{ $json.lead_search_id }}` |
| `source` | `n8n_search` |

### Fallback Logic
If n8n does not yet support direct `user_id` mapping, Laravel implements a **Retroactive Claiming System**.

1. Laravel stores the `lead_search_id` and `user_id` locally.
2. After the n8n webhook returns success, Laravel runs `claimLeadsForSearch()`.
3. It searches for leads with `user_id IS NULL` that match the search parameters (Country, City, Industry, Position) and were created within 5 minutes of the search start.
4. It then assigns those leads to the initiating user.

## Admin Reassignment

Admins can manually reassign "Orphan" leads (where `user_id` is null) via the **Admin -> Unassigned Leads** panel.

1. Navigate to `/admin/leads/unassigned`.
2. Select the leads.
3. Choose the target user from the dropdown.
4. Click **Assign**.
