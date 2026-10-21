-- Fixed, reviewed, read-only query (runbook §5.8 and §8.5). Production table prefix: casbmqt_.
-- Reads the two options that decide Tutor's first-admin-request welcome redirect (tutor/classes/Admin.php:974-986).
-- Expected before the window: tutor_wizard = active, and tutor-new-feature absent (no row).
-- Expected after the first admin request of the window: tutor-new-feature = the installed Tutor version.
SELECT option_name, option_value FROM casbmqt_options WHERE option_name IN ('tutor_wizard', 'tutor-new-feature');
