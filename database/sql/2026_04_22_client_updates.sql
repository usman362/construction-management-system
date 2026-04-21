-- =============================================================================
-- Client update batch — 2026-04-22
-- Safe to paste into phpMyAdmin → SQL tab on the live server.
--
-- What this does:
--   1. timesheets.work_order_number   — optional shop-internal WO # field
--      (single + bulk timesheet forms).
--   2. employees.default_cost_type_id — FK → cost_types so Bulk Timesheet Entry
--      can pre-fill each row's Cost Type dropdown from the employee file.
--   3. project_billable_rates.base_ot_hourly_rate — dedicated OT base rate so
--      the OT Billable calc can use union/prevailing-wage OT wages instead of
--      the blind 1.5× of ST base.
--
-- Also inserts matching rows into the Laravel `migrations` table so that a
-- future `php artisan migrate` won't try to re-add these columns.
--
-- Every statement is idempotent: re-running the whole file is a no-op once
-- the columns already exist.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. timesheets.work_order_number
-- -----------------------------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'timesheets'
      AND COLUMN_NAME  = 'work_order_number'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `timesheets` ADD COLUMN `work_order_number` VARCHAR(100) NULL AFTER `shift_id`',
    'SELECT "timesheets.work_order_number already exists — skipped" AS note'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- -----------------------------------------------------------------------------
-- 2. employees.default_cost_type_id (FK → cost_types)
-- -----------------------------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'employees'
      AND COLUMN_NAME  = 'default_cost_type_id'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `employees` ADD COLUMN `default_cost_type_id` BIGINT UNSIGNED NULL AFTER `classification`',
    'SELECT "employees.default_cost_type_id already exists — skipped" AS note'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- FK separately so it only runs when the column was actually just added.
SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA        = DATABASE()
      AND TABLE_NAME          = 'employees'
      AND COLUMN_NAME         = 'default_cost_type_id'
      AND REFERENCED_TABLE_NAME = 'cost_types'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE `employees`
       ADD CONSTRAINT `employees_default_cost_type_id_foreign`
       FOREIGN KEY (`default_cost_type_id`) REFERENCES `cost_types` (`id`)
       ON DELETE SET NULL',
    'SELECT "employees.default_cost_type_id FK already exists — skipped" AS note'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- -----------------------------------------------------------------------------
-- 3. project_billable_rates.base_ot_hourly_rate
-- -----------------------------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'project_billable_rates'
      AND COLUMN_NAME  = 'base_ot_hourly_rate'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `project_billable_rates` ADD COLUMN `base_ot_hourly_rate` DECIMAL(10,4) NULL AFTER `base_hourly_rate`',
    'SELECT "project_billable_rates.base_ot_hourly_rate already exists — skipped" AS note'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- -----------------------------------------------------------------------------
-- 4. budget_lines — relax unique key from (project_id, cost_code_id)
--    to (project_id, cost_code_id, cost_type_id) so the same phase code
--    can be budgeted twice on a project (e.g. Direct Labor + Indirect Labor
--    both on 01.10.000).
-- -----------------------------------------------------------------------------
SET @old_idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'budget_lines'
      AND INDEX_NAME   = 'budget_lines_project_id_cost_code_id_unique'
);
SET @sql := IF(@old_idx > 0,
    'ALTER TABLE `budget_lines` DROP INDEX `budget_lines_project_id_cost_code_id_unique`',
    'SELECT "budget_lines old unique index already dropped — skipped" AS note'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @new_idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'budget_lines'
      AND INDEX_NAME   = 'budget_lines_project_cost_code_cost_type_unique'
);
SET @sql := IF(@new_idx = 0,
    'ALTER TABLE `budget_lines` ADD UNIQUE KEY `budget_lines_project_cost_code_cost_type_unique` (`project_id`, `cost_code_id`, `cost_type_id`)',
    'SELECT "budget_lines new unique index already exists — skipped" AS note'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- -----------------------------------------------------------------------------
-- 5. Record these migrations in Laravel's `migrations` table so
--    `php artisan migrate` will NOT try to re-run them on the live server.
--    Uses a single new batch = (max existing batch + 1). Per-row existence
--    check (INSERT … SELECT … WHERE NOT EXISTS) so re-running the file is safe
--    — Laravel's migrations table has no UNIQUE on `migration`, which makes
--    INSERT IGNORE insufficient.
-- -----------------------------------------------------------------------------
SET @next_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_22_000001_add_work_order_number_to_timesheets', @next_batch
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_04_22_000001_add_work_order_number_to_timesheets'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_22_000002_add_default_cost_type_to_employees', @next_batch
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_04_22_000002_add_default_cost_type_to_employees'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_22_000003_add_base_ot_hourly_rate_to_project_billable_rates', @next_batch
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_04_22_000003_add_base_ot_hourly_rate_to_project_billable_rates'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_22_000004_relax_budget_lines_unique_to_include_cost_type', @next_batch
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_04_22_000004_relax_budget_lines_unique_to_include_cost_type'
);


-- -----------------------------------------------------------------------------
-- Verification — optional; run these to confirm the columns landed.
-- -----------------------------------------------------------------------------
-- SHOW COLUMNS FROM `timesheets`             LIKE 'work_order_number';
-- SHOW COLUMNS FROM `employees`              LIKE 'default_cost_type_id';
-- SHOW COLUMNS FROM `project_billable_rates` LIKE 'base_ot_hourly_rate';
-- SHOW INDEX FROM `budget_lines` WHERE Key_name LIKE '%unique';
-- SELECT * FROM `migrations` WHERE `migration` LIKE '2026_04_22_%';
