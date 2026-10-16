SELECT tablename FROM pg_tables WHERE schemaname='public' AND tablename IN ('users','password_reset_tokens','cache','cache_locks','jobs','job_batches','failed_jobs','sessions');
