SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name='migrations' AND table_schema='public';
SELECT indexname, indexdef FROM pg_indexes WHERE tablename='migrations' AND schemaname='public';
