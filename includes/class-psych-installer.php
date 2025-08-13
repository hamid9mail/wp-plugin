<?php
/**
 * Psych Complete System Installer
 *
 * @package Psych_Complete_System
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Psych_Installer {

    /**
     * Run the installer.
     */
    public static function install() {
        self::create_tables();
    }

    /**
     * Create the necessary database tables.
     */
    private static function create_tables() {
        global $wpdb;

        $collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $tables_sql = self::get_schema();
        dbDelta($tables_sql);
    }

    /**
     * Get the database schema.
     *
     * @return string The SQL for the tables.
     */
    private static function get_schema() {
        global $wpdb;

        $results_table_name = $wpdb->prefix . 'psych_mission_results';
        $station_state_table_name = $wpdb->prefix . 'psych_station_user_state';
        $rewards_table_name = $wpdb->prefix . 'psych_user_rewards';

        $sql = "
        CREATE TABLE {$results_table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            actor_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            mission_id VARCHAR(255) NOT NULL,
            mission_type VARCHAR(100) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            payload_json LONGTEXT NULL,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY mission_id (mission_id)
        ) $collate;

        CREATE TABLE {$station_state_table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            station_id VARCHAR(255) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'locked',
            progress_json TEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_station (user_id, station_id)
        ) $collate;

        CREATE TABLE {$rewards_table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            mission_id VARCHAR(255) NULL,
            station_id VARCHAR(255) NULL,
            reward_type VARCHAR(100) NOT NULL,
            params_json TEXT NULL,
            applied_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $collate;
        ";

        return $sql;
    }
}
