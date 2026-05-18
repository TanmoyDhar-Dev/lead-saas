# n8n Lead Search Mapping & Documentation

To ensure leads scraped by n8n are correctly linked to users and search queries in LeadFlow, the following mapping must be implemented in the n8n workflow.

## 1. Webhook Payload Awareness
When Laravel triggers the n8n lead scraping webhook, it sends the following fields:

| Field | Description |
| :--- | :--- |
| `country` | Target country (e.g., "United States") |
| `city` | Target city (e.g., "San Francisco") |
| `industry` | Target industry (e.g., "Software Development") |
| `position` | Target job title (e.g., "CEO" OR "Founder") |
| `volume` | Number of leads requested (Max: 100) |
| `user_id` | UUID of the user who started the search |
| `lead_search_id` | UUID of the `lead_searches` record |

## 2. Lead Mapping (HTTP Request / Database Node)
When n8n pushes data back to the database or an API endpoint that creates leads, ensure the following fields are mapped:

### Required Mappings:
- **`user_id`**: Map from the initial webhook body.
  - Expression: `={{ $('Lead Search Trigger').first().json.body.user_id || null }}`
- **`lead_search_id`**: Map from the initial webhook body.
  - Expression: `={{ $('Lead Search Trigger').first().json.body.lead_search_id || null }}`
- **`source`**: Set to `n8n_search`.

### Volume Restriction:
- The n8n workflow **must respect the `volume` field**.
- If `volume` is 50, the workflow should not return more than 50 leads.
- Use a "Limit" or "Stop" node in n8n after the collection loop to enforce this.

## 3. Data Integrity
- Do not modify `lead_search_id` if a lead is updated.
- If a lead already exists (UPSERT), ensure it is still linked to the latest `lead_search_id` that found it, or keep the original if preferred. However, for "View Leads" by query to work, the lead must have the correct `lead_search_id`.

## 4. Status Reporting (Optional)
Once the n8n scraping is finished, it is recommended to call a callback URL (if implemented) or update the `lead_searches` table status to `completed`.
- `PATCH /api/lead-searches/{{ lead_search_id }}`
- Body: `{ "status": "completed" }`
