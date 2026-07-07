-- MasterRent - Slice 1
-- File: sql/000_database.sql
-- Purpose: create the initial database for XAMPP/MySQL/MariaDB.
-- Run order:
--   1) 000_database.sql
--   2) 001_auth_schema.sql
--   3) 002_auth_seed.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `masterrent`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `masterrent`;
