DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '0001_01_01_000000_create_users_table') THEN
        INSERT INTO migrations (migration, batch) VALUES ('0001_01_01_000000_create_users_table', 0);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '0001_01_01_000001_create_cache_table') THEN
        INSERT INTO migrations (migration, batch) VALUES ('0001_01_01_000001_create_cache_table', 0);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '0001_01_01_000002_create_jobs_table') THEN
        INSERT INTO migrations (migration, batch) VALUES ('0001_01_01_000002_create_jobs_table', 0);
    END IF;
END $$;
