<?php
/**
 * Frontend Website Settings Loader
 * Location: public/includes/settings.php
 */

function setting($key, $default = null)
{
    static $settings = null;

    if ($settings === null) {

        // Load DB connection
        require_once __DIR__ . '/../../includes/db.php';

        // 🔴 THIS WAS MISSING
        global $connection;

        if (!isset($connection) || !$connection) {
            return $default;
        }

        $settings = [];

        $res = $connection->query(
            "SELECT setting_key, setting_value FROM website_settings"
        );

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }

    return $settings[$key] ?? $default;
}
