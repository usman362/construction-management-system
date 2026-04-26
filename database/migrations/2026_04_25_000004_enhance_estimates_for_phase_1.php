<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estimating Phase 1 — enrich the `estimates` table.
 *
 * Adds:
 *   - client_id              FK to clients (multi-client estimates)
 *   - estimate_type          'standard' | 'change_order' | 'allowance'
 *   - total_cost / total_price / margin_percent (auto-calculated by observer)
 *   - valid_from / valid_until / start_date / end_date / duration_days
 *   - sent_to_client_date / client_response_date / converted_to_project_id
 *   - terms_and_conditions / assumed_exclusions
 *
 * Status enum is widened to include the full bid lifecycle. Existing 'draft' /
 * 'submitted' / 'approved' / 'revised' rows continue to work; we add 'sent_to_client',
 * 'accepted', 'rejected', 'converted_to_project'.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            // Multi-client support — a Project may not exist yet when estimating
            // begins, so estimates can be tied to a Client directly.
            $table->foreignId('client_id')->nullable()->after('project_id')
                ->constrained('clients')->nullOnDelete();

            $table->string('estimate_type', 32)->default('standard')->after('name');

            // Totals — populated by EstimateObserver as lines change.
            $table->decimal('total_cost', 15, 2)->default(0)->after('total_amount');
            $table->decimal('total_price', 15, 2)->default(0)->after('total_cost');
            $table->decimal('margin_percent', 6, 4)->default(0)->after('total_price');

            // Validity & timeline
            $table->date('valid_from')->nullable()->after('margin_percent');
            $table->date('valid_until')->nullable()->after('valid_from');
            $table->date('start_date')->nullable()->after('valid_until');
            $table->date('end_date')->nullable()->after('start_date');
            $table->integer('duration_days')->nullable()->after('end_date');

            // Client send/accept tracking
            $table->timestamp('sent_to_client_date')->nullable()->after('duration_days');
            $table->timestamp('client_response_date')->nullable()->after('sent_to_client_date');
            $table->foreignId('converted_to_project_id')->nullable()->after('client_response_date')
                ->constrained('projects')->nullOnDelete();

            // Legal text
            $table->text('terms_and_conditions')->nullable()->after('converted_to_project_id');
            $table->text('assumed_exclusions')->nullable()->after('terms_and_conditions');
        });

        // Widen the status enum. Doing this with raw SQL because Laravel's
        // change() requires doctrine/dbal and we want the migration to work
        // without that dependency.
        DB::statement("
            ALTER TABLE estimates MODIFY COLUMN status
            ENUM('draft','submitted','sent_to_client','accepted','rejected','approved','revised','converted_to_project')
            DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
            $table->dropConstrainedForeignId('converted_to_project_id');
            $table->dropColumn([
                'estimate_type',
                'total_cost', 'total_price', 'margin_percent',
                'valid_from', 'valid_until', 'start_date', 'end_date', 'duration_days',
                'sent_to_client_date', 'client_response_date',
                'terms_and_conditions', 'assumed_exclusions',
            ]);
        });

        DB::statement("ALTER TABLE estimates MODIFY COLUMN status ENUM('draft','submitted','approved','revised') DEFAULT 'draft'");
    }
};
