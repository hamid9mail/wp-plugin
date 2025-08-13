<?php
/**
 * Abstract Mission Type Class
 *
 * Defines the structure for all mission type handlers.
 *
 * @package Psych_Complete_System
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Psych_Mission_Type {

    /**
     * The unique identifier for the mission type.
     * @var string
     */
    protected $type;

    /**
     * Constructor.
     */
    public function __construct() {
        // Can be used for setting up hooks specific to the mission type
    }

    /**
     * Renders the frontend display for the mission.
     * This could be a button, a form, a video player, etc.
     *
     * @param array $mission The mission data array.
     * @param array $context The current user/viewing context.
     * @return string The HTML to display.
     */
    abstract public function render(array $mission, array $context): string;

    /**
     * Handles an event that might complete the mission.
     * This is called by hooks (e.g., gform_after_submission, wc_order_status_completed).
     *
     * @param string $event The name of the event.
     * @param array $payload The data associated with the event.
     * @return void
     */
    abstract public function handle_event(string $event, array $payload): void;

    /**
     * Checks if the mission is completed for a given user.
     *
     * @param int $user_id The ID of the user.
     * @param array $mission The mission data array.
     * @return bool True if completed, false otherwise.
     */
    abstract public function is_completed(int $user_id, array $mission): bool;

    /**
     * Gets the structured result data for a completed mission.
     *
     * @param int $user_id The ID of the user.
     * @param array $mission The mission data array.
     * @return array The structured result data.
     */
    abstract public function get_result(int $user_id, array $mission): array;

}
