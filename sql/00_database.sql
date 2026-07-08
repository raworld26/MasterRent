-- UniAffitti L'Aquila — Fase 2
-- File: sql/00_database.sql
-- Crea il database dedicato alla Fase 2 (separato da quello della Fase 1).
--
-- Ordine di importazione (sequenza unica):
--   00_database  -> 01_auth_schema -> 02_geo_schema -> 03_properties_schema
--   -> 04_auth_seed -> 05_geo_seed -> 06_demo_seed

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `uniaffitti`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `uniaffitti`;
