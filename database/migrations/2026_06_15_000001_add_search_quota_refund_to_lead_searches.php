<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_searches', function (Blueprint $table) {
            $table->boolean('quota_charged')->default(false)->after('status');
            $table->timestamp('quota_refunded_at')->nullable()->after('quota_charged');
        });

        if (Schema::hasTable('refund_quota_trigger')) {
            Schema::drop('refund_quota_trigger');
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION refund_search_quota_if_eligible()
RETURNS TRIGGER AS $$
DECLARE
    lead_count INTEGER;
    should_refund BOOLEAN := FALSE;
BEGIN
    IF NEW.status IS NOT DISTINCT FROM OLD.status THEN
        RETURN NEW;
    END IF;

    IF NEW.quota_charged IS NOT TRUE OR NEW.quota_refunded_at IS NOT NULL THEN
        RETURN NEW;
    END IF;

    IF NEW.status = 'failed' THEN
        should_refund := TRUE;
    ELSIF NEW.status = 'completed' THEN
        SELECT COUNT(*) INTO lead_count
        FROM lead_user
        WHERE lead_search_id = NEW.id;

        IF lead_count = 0 THEN
            should_refund := TRUE;
        END IF;
    ELSE
        RETURN NEW;
    END IF;

    IF NOT should_refund THEN
        RETURN NEW;
    END IF;

    UPDATE user_plans
    SET searches_used = GREATEST(searches_used - 1, 0),
        updated_at = NOW()
    WHERE user_id = NEW.user_id;

    NEW.quota_refunded_at := NOW();

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS lead_searches_refund_quota_trigger ON lead_searches;
CREATE TRIGGER lead_searches_refund_quota_trigger
    BEFORE UPDATE ON lead_searches
    FOR EACH ROW
    EXECUTE FUNCTION refund_search_quota_if_eligible();
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS lead_searches_refund_quota_trigger ON lead_searches;
DROP FUNCTION IF EXISTS refund_search_quota_if_eligible();
SQL);
        }

        Schema::table('lead_searches', function (Blueprint $table) {
            $table->dropColumn(['quota_charged', 'quota_refunded_at']);
        });
    }
};
