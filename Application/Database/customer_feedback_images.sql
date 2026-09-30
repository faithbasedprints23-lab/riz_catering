-- Run once on an existing Riz Catering database to enable optional review photos.
ALTER TABLE feedback ADD COLUMN image_path VARCHAR(500) DEFAULT NULL AFTER comments;
