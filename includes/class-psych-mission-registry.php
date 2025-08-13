<?php
/**
 * Mission Type Registry
 *
 * Manages the registration and retrieval of mission type handlers.
 *
 * @package Psych_Complete_System
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Psych_Mission_Registry {

    /**
     * The single instance of the class.
     * @var Psych_Mission_Registry
     */
    private static $instance = null;

    /**
     * The array of registered mission type handlers.
     * @var array
     */
    private $mission_types = [];

    /**
     * Get the single instance of the class.
     *
     * @return Psych_Mission_Registry
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {
        // We can auto-register core mission types here in the future
        add_action('init', [$this, 'register_core_mission_types'], 5);
    }

    /**
     * Register a new mission type.
     *
     * @param string $type The unique identifier for the mission type (e.g., 'gform', 'video_spotplayer').
     * @param string $class_name The name of the class that handles this mission type.
     */
    public function register(string $type, string $class_name) {
        if (class_exists($class_name)) {
            $this->mission_types[$type] = new $class_name();
        }
    }

    /**
     * Get the handler for a specific mission type.
     *
     * @param string $type The mission type identifier.
     * @return Psych_Mission_Type|null The handler object, or null if not found.
     */
    public function get_handler(string $type): ?Psych_Mission_Type {
        return $this->mission_types[$type] ?? null;
    }

    /**
     * Get all registered mission types.
     *
     * @return array
     */
    public function get_all_types(): array {
        return $this->mission_types;
    }

    /**
     * Register the core mission types that are part of the plugin.
     */
    public function register_core_mission_types() {
        // In the future, we will register our concrete classes here.
        // For example:
        // require_once __DIR__ . '/missions/class-psych-mission-type-gform.php';
        // $this->register('gform', 'Psych_Mission_Type_GForm');

        // require_once __DIR__ . '/missions/class-psych-mission-type-purchase.php';
        // $this->register('purchase', 'Psych_Mission_Type_Purchase');
    }
}

/**
 * Helper function to access the registry.
 *
 * @return Psych_Mission_Registry
 */
function Psych_Mission_Registry() {
    return Psych_Mission_Registry::instance();
}

// Initialize the registry
Psych_Mission_Registry();
