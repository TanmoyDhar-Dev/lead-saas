<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION leads_preserve_ownership()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'UPDATE' THEN
        IF NEW.user_id IS NOT NULL THEN
            INSERT INTO lead_user (user_id, lead_id, lead_search_id, created_at, updated_at)
            VALUES (NEW.user_id, OLD.id, NEW.lead_search_id, NOW(), NOW())
            ON CONFLICT (user_id, lead_id) DO UPDATE
                SET lead_search_id = EXCLUDED.lead_search_id,
                    updated_at = NOW();
        END IF;

        IF OLD.user_id IS NOT NULL THEN
            NEW.user_id := OLD.user_id;
            NEW.lead_search_id := OLD.lead_search_id;
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION leads_register_owner()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.user_id IS NOT NULL THEN
        INSERT INTO lead_user (user_id, lead_id, lead_search_id, created_at, updated_at)
        VALUES (NEW.user_id, NEW.id, NEW.lead_search_id, NOW(), NOW())
        ON CONFLICT (user_id, lead_id) DO UPDATE
            SET lead_search_id = EXCLUDED.lead_search_id,
                updated_at = NOW();
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS leads_preserve_ownership_trigger ON leads;
CREATE TRIGGER leads_preserve_ownership_trigger
    BEFORE UPDATE ON leads
    FOR EACH ROW
    EXECUTE FUNCTION leads_preserve_ownership();

DROP TRIGGER IF EXISTS leads_register_owner_trigger ON leads;
CREATE TRIGGER leads_register_owner_trigger
    AFTER INSERT ON leads
    FOR EACH ROW
    EXECUTE FUNCTION leads_register_owner();
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS leads_preserve_ownership_trigger ON leads;
DROP TRIGGER IF EXISTS leads_register_owner_trigger ON leads;
DROP FUNCTION IF EXISTS leads_preserve_ownership();
DROP FUNCTION IF EXISTS leads_register_owner();
SQL);
    }
};
