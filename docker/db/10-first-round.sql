-- Schedules round 1. cron2/newRoundUpdater.py (run every minute by the cron container) starts
-- it: it generates the NPC world, bank accounts and the first rankings.
INSERT INTO round (name, startDate, status) VALUES ('Round 1', NOW(), 0);
