<?php
/**
 * Condition Evaluator
 *
 * Parses and evaluates condition strings for rewards and branching.
 *
 * @package Psych_Complete_System
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Psych_Condition_Evaluator {

    /**
     * Evaluates a condition string against a given context.
     *
     * @param string $condition_string The condition to evaluate (e.g., "result.score > 10").
     * @param array $context The data context to check against.
     * @return bool True if the condition is met, false otherwise.
     */
    public static function evaluate(string $condition_string, array $context): bool {
        if (empty($condition_string)) {
            return true; // An empty condition is always true.
        }

        // Simple parser for "key operator value" format.
        // Example: "result.score >= 5"
        preg_match('/^([a-zA-Z0-9_.]+)\s*([<>=!]+)\s*(.+)$/', $condition_string, $matches);

        if (count($matches) !== 4) {
            // For now, we only support this simple format.
            // In the future, this could be expanded to a full expression parser.
            return false;
        }

        list(, $key, $operator, $expected_value) = $matches;

        // Get the actual value from the context using the key.
        // The key can be nested, e.g., "result.anxiety_score".
        $actual_value = self::get_value_from_context($key, $context);

        if (is_null($actual_value)) {
            return false; // Key not found in context.
        }

        // Trim quotes from expected value if they exist
        $expected_value = trim($expected_value, "'\"");

        // Type juggling for comparison
        $actual_value_numeric = is_numeric($actual_value) ? floatval($actual_value) : $actual_value;
        $expected_value_numeric = is_numeric($expected_value) ? floatval($expected_value) : $expected_value;

        if (is_numeric($actual_value_numeric) && is_numeric($expected_value_numeric)) {
            $actual_value = $actual_value_numeric;
            $expected_value = $expected_value_numeric;
        }

        switch ($operator) {
            case '==':
                return $actual_value == $expected_value;
            case '!=':
                return $actual_value != $expected_value;
            case '>':
                return $actual_value > $expected_value;
            case '>=':
                return $actual_value >= $expected_value;
            case '<':
                return $actual_value < $expected_value;
            case '<=':
                return $actual_value <= $expected_value;
            default:
                return false;
        }
    }

    /**
     * Helper to get a value from the context array using a dot-notation key.
     *
     * @param string $key The dot-notation key (e.g., "user.meta.level").
     * @param array $context The context array.
     * @return mixed|null The value, or null if not found.
     */
    private static function get_value_from_context(string $key, array $context) {
        $keys = explode('.', $key);
        $value = $context;
        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        return $value;
    }
}
