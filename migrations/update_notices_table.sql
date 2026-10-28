-- Migration: Add fields to notices table for gender-specific notice routing
-- This allows notices to be targeted to specific genders based on admin type

ALTER TABLE notices ADD COLUMN posted_by_role VARCHAR(20) DEFAULT 'admin';
ALTER TABLE notices ADD COLUMN target_gender VARCHAR(20) DEFAULT 'all';

-- posted_by_role can be: 'admin', 'male_admin', 'female_admin'
-- target_gender can be: 'male', 'female', 'all'
