<?php
/**
 * Portable Badge Engine
 * - JSON-based definitions
 * - Declarative rules
 * - Platform-agnostic event model
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Workout_Tracker_Badge_Engine {
    private static $definitions_cache = null;

    public static function init() {
        Workout_Tracker_Database::create_badge_tables();
        self::maybe_seed_default_definitions();
    }

    private static function definition_table() {
        global $wpdb;
        return $wpdb->prefix . 'workout_tracker_badge_definitions';
    }

    private static function events_table() {
        global $wpdb;
        return $wpdb->prefix . 'workout_tracker_badge_events';
    }

    private static function state_table() {
        global $wpdb;
        return $wpdb->prefix . 'workout_tracker_badge_state';
    }

    /**
     * Seed definitions from canonical JSON when table is empty
     */
    private static function maybe_seed_default_definitions() {
        global $wpdb;
        $table = self::definition_table();
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        if ( $count > 0 ) {
            return;
        }

        self::import_definitions_from_file( WORKOUT_TRACKER_PLUGIN_DIR . 'assets/badges/badges.json', false );
    }

    /**
     * Import definitions from a JSON file. Optionally replace existing rows.
     */
    public static function import_definitions_from_file( $file, $replace_existing = false ) {
        global $wpdb;
        $table = self::definition_table();
        if ( $replace_existing ) {
            $wpdb->query( "TRUNCATE TABLE {$table}" );
        }

        $file = is_string( $file ) && $file ? $file : WORKOUT_TRACKER_PLUGIN_DIR . 'assets/badges/badges.json';
        if ( ! file_exists( $file ) ) {
            return false;
        }

        $json = file_get_contents( $file );
        $data = json_decode( $json, true );
        if ( ! is_array( $data ) ) {
            return false;
        }

        foreach ( $data as $def ) {
            self::save_definition( $def );
        }

        return true;
    }

    /**
     * Upsert a single badge definition (portable JSON stored in DB)
     */
    public static function save_definition( $definition ) {
        global $wpdb;
        $table    = self::definition_table();
        $badge_id = isset( $definition['id'] ) ? sanitize_key( $definition['id'] ) : '';
        if ( ! $badge_id ) {
            return false;
        }

        $definition['id']     = $badge_id;
        $definition['active'] = array_key_exists( 'active', $definition ) ? (bool) $definition['active'] : true;
        $definition['hidden'] = array_key_exists( 'hidden', $definition ) ? (bool) $definition['hidden'] : false;

        $json = wp_json_encode( $definition );

        $result = (bool) $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table} (badge_id, name, category, active, definition_json, updated_at)
                 VALUES (%s, %s, %s, %d, %s, NOW())
                 ON DUPLICATE KEY UPDATE name = VALUES(name), category = VALUES(category), active = VALUES(active), definition_json = VALUES(definition_json), updated_at = NOW()",
                $badge_id,
                isset( $definition['name'] ) ? sanitize_text_field( $definition['name'] ) : $badge_id,
                isset( $definition['category'] ) ? sanitize_text_field( $definition['category'] ) : 'general',
                $definition['active'] ? 1 : 0,
                $json
            )
        );

        self::$definitions_cache = null;
        return $result;
    }

    /**
     * Fetch all definitions
     */
    public static function get_definitions( $active_only = true ) {
        if ( null !== self::$definitions_cache ) {
            if ( $active_only ) {
                return array_values( array_filter( self::$definitions_cache, function( $d ) {
                    return ! empty( $d['active'] );
                } ) );
            }
            return self::$definitions_cache;
        }

        global $wpdb;
        $rows = $wpdb->get_results( "SELECT badge_id, definition_json, active FROM " . self::definition_table(), ARRAY_A );
        $defs = array();
        foreach ( $rows as $row ) {
            $def = json_decode( $row['definition_json'], true );
            if ( ! is_array( $def ) ) {
                continue;
            }
            $def['id']     = isset( $def['id'] ) ? sanitize_key( $def['id'] ) : sanitize_key( $row['badge_id'] );
            $def['active'] = (bool) $row['active'];
            $defs[ $def['id'] ] = $def;
        }
        self::$definitions_cache = $defs;
        return $active_only ? array_values( array_filter( $defs, function( $d ) {
            return ! empty( $d['active'] );
        } ) ) : array_values( $defs );
    }

    /**
     * Emit an event and evaluate badges for the user
     */
    public static function emit_event( $user_id, $event, $payload = array(), $occurred_at = null ) {
        if ( empty( $user_id ) || empty( $event ) ) {
            return array();
        }

        global $wpdb;
        $table       = self::events_table();
        $occurred_at = $occurred_at ? gmdate( 'Y-m-d H:i:s', strtotime( $occurred_at ) ) : current_time( 'mysql' );
        $wpdb->insert(
            $table,
            array(
                'user_id'     => (int) $user_id,
                'event'       => sanitize_text_field( $event ),
                'occurred_at' => $occurred_at,
                'payload_json'=> wp_json_encode( $payload ),
            ),
            array( '%d', '%s', '%s', '%s' )
        );

        return self::evaluate_for_event( $user_id, $event, $payload, $occurred_at );
    }

    /**
     * Get badges (definitions merged with user state)
     */
    public static function get_user_badges( $user_id ) {
        global $wpdb;
        $defs        = self::get_definitions( false );
        $state_rows  = $wpdb->get_results( $wpdb->prepare( "SELECT badge_id, earned, earned_at, progress, progress_data FROM " . self::state_table() . " WHERE user_id = %d", $user_id ), ARRAY_A );
        $state_index = array();
        foreach ( $state_rows as $row ) {
            $state_index[ $row['badge_id'] ] = $row;
        }

        $badges = array();
        foreach ( $defs as $def ) {
            $badge_id = $def['id'];
            $state    = isset( $state_index[ $badge_id ] ) ? $state_index[ $badge_id ] : null;
            $progress = $state ? (float) $state['progress'] : 0;
            $badge_asset = 'assets/badges/' . $badge_id . '.png';
            $badge_path  = WORKOUT_TRACKER_PLUGIN_DIR . $badge_asset;
            if ( file_exists( $badge_path ) ) {
                $icon = WORKOUT_TRACKER_PLUGIN_URL . $badge_asset;
            } else {
                $icon = $def['icon'] ?? 'award';
            }
            $badges[] = array(
                'id'           => $badge_id,
                'slug'         => $badge_id, // backward compat with UI
                'name'         => $def['name'] ?? $badge_id,
                'title'        => $def['name'] ?? $badge_id,
                'description'  => $def['description'] ?? '',
                'icon'         => $icon,
                'category'     => $def['category'] ?? 'general',
                'trigger'      => $def['trigger'] ?? '',
                'rules'        => $def['rules'] ?? array(),
                'hidden'       => ! empty( $def['hidden'] ),
                'active'       => ! empty( $def['active'] ),
                'earned'       => $state ? (bool) $state['earned'] : false,
                'earned_at'    => $state ? $state['earned_at'] : null,
                'progress'     => $progress,
                'progress_data'=> $state && $state['progress_data'] ? json_decode( $state['progress_data'], true ) : null,
            );
        }

        return $badges;
    }

    /**
     * Evaluate all badges triggered by the event
     */
    private static function evaluate_for_event( $user_id, $event, $payload, $occurred_at ) {
        global $wpdb;
        $awarded = array();
        $defs = array_filter( self::get_definitions( true ), function( $def ) use ( $event ) {
            return isset( $def['trigger'] ) && $def['trigger'] === $event;
        } );

        if ( empty( $defs ) ) {
            return $awarded;
        }

        $state_table = self::state_table();
        foreach ( $defs as $def ) {
            $badge_id = $def['id'];
            // Skip already earned
            $earned = $wpdb->get_var( $wpdb->prepare( "SELECT earned FROM {$state_table} WHERE user_id = %d AND badge_id = %s AND earned = 1", $user_id, $badge_id ) );
            if ( $earned ) {
                continue;
            }

            $result = self::evaluate_definition( $def, $user_id, $payload, $occurred_at );
            self::upsert_state( $user_id, $badge_id, $result );

            if ( $result['passed'] ) {
                $awarded[] = $badge_id;
            }
        }

        return $awarded;
    }

    private static function upsert_state( $user_id, $badge_id, $result ) {
        global $wpdb;
        $table = self::state_table();
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table} (user_id, badge_id, earned, earned_at, progress, progress_data)
                 VALUES (%d, %s, %d, %s, %f, %s)
                 ON DUPLICATE KEY UPDATE earned = VALUES(earned), earned_at = VALUES(earned_at), progress = VALUES(progress), progress_data = VALUES(progress_data)",
                (int) $user_id,
                $badge_id,
                $result['passed'] ? 1 : 0,
                $result['passed'] ? $result['earned_at'] : null,
                isset( $result['progress'] ) ? (float) $result['progress'] : 0,
                $result['progress_data'] ? wp_json_encode( $result['progress_data'] ) : null
            )
        );
    }

    private static function evaluate_definition( $definition, $user_id, $payload, $occurred_at ) {
        $rules          = isset( $definition['rules'] ) && is_array( $definition['rules'] ) ? $definition['rules'] : array();
        $rule_progress  = array();
        $passed         = true;

        foreach ( $rules as $rule ) {
            $check = self::evaluate_rule( $rule, $user_id, $payload, $occurred_at );
            $rule_progress[] = $check['progress'];
            if ( ! $check['passed'] ) {
                $passed = false;
            }
        }

        $progress = empty( $rule_progress ) ? 0 : min( 1, min( $rule_progress ) );

        return array(
            'passed'        => $passed,
            'earned_at'     => $passed ? $occurred_at : null,
            'progress'      => $progress,
            'progress_data' => array( 'rules' => $rule_progress ),
        );
    }

    private static function evaluate_rule( $rule, $user_id, $payload, $occurred_at ) {
        $type = isset( $rule['type'] ) ? $rule['type'] : '';
        switch ( $type ) {
            case 'threshold':
                return self::evaluate_threshold_rule( $rule, $user_id, $occurred_at );
            case 'streak':
                return self::evaluate_streak_rule( $rule, $user_id, $occurred_at );
            case 'time_window':
                return self::evaluate_time_window_rule( $rule, $payload, $occurred_at );
            case 'payload_compare':
                return self::evaluate_payload_compare_rule( $rule, $payload );
            case 'payload_flag':
                return self::evaluate_payload_flag_rule( $rule, $payload );
            default:
                return array( 'passed' => false, 'progress' => 0 );
        }
    }

    private static function evaluate_threshold_rule( $rule, $user_id, $occurred_at ) {
        global $wpdb;
        $event = $rule['event'] ?? '';
        $value = isset( $rule['value'] ) ? (int) $rule['value'] : 0;
        if ( ! $event || $value <= 0 ) {
            return array( 'passed' => false, 'progress' => 0 );
        }
        $window_days = isset( $rule['within_days'] ) ? (int) $rule['within_days'] : null;

        $sql = "SELECT COUNT(*) FROM " . self::events_table() . " WHERE user_id = %d AND event = %s";
        $params = array( $user_id, $event );
        if ( $window_days ) {
            $sql    .= " AND occurred_at >= DATE_SUB(%s, INTERVAL %d DAY)";
            $params[] = $occurred_at;
            $params[] = $window_days;
        }
        $prepared = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $params ) );
        $count   = (int) $wpdb->get_var( $prepared );
        $progress = $value > 0 ? min( 1, $count / $value ) : 0;
        $passed   = $count >= $value;
        return array( 'passed' => $passed, 'progress' => $progress );
    }

    private static function evaluate_streak_rule( $rule, $user_id, $occurred_at ) {
        global $wpdb;
        $event = $rule['event'] ?? '';
        $required = isset( $rule['min_consecutive_days'] ) ? (int) $rule['min_consecutive_days'] : 0;
        if ( ! $event || $required <= 0 ) {
            return array( 'passed' => false, 'progress' => 0 );
        }
        $window_days = isset( $rule['within_days'] ) ? (int) $rule['within_days'] : null;

        $sql = "SELECT DISTINCT DATE(occurred_at) as day
                FROM " . self::events_table() . "
                WHERE user_id = %d AND event = %s";
        $params = array( $user_id, $event );
        if ( $window_days ) {
            $sql    .= " AND occurred_at >= DATE_SUB(%s, INTERVAL %d DAY)";
            $params[] = $occurred_at;
            $params[] = $window_days;
        }
        $sql     .= " ORDER BY day DESC";
        $prepared = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $params ) );
        $days     = $wpdb->get_col( $prepared );

        $streak   = 0;
        $prev_day = null;
        foreach ( $days as $day ) {
            if ( null === $prev_day ) {
                $streak   = 1;
                $prev_day = $day;
                continue;
            }
            $diff = ( strtotime( $prev_day ) - strtotime( $day ) ) / DAY_IN_SECONDS;
            if ( 1 === $diff ) {
                $streak++;
            } elseif ( $diff > 1 ) {
                break;
            }
            $prev_day = $day;
        }

        $progress = $required > 0 ? min( 1, $streak / $required ) : 0;
        return array( 'passed' => $streak >= $required, 'progress' => $progress );
    }

    private static function evaluate_time_window_rule( $rule, $payload, $occurred_at ) {
        $start = isset( $rule['hour_start'] ) ? (int) $rule['hour_start'] : 0;
        $end   = isset( $rule['hour_end'] ) ? (int) $rule['hour_end'] : 0;
        $hour  = isset( $payload['hour'] ) ? (int) $payload['hour'] : (int) gmdate( 'G', strtotime( $occurred_at ) );
        $in_window = ( $hour >= $start && $hour < $end );
        $min_occurrences = isset( $rule['min_occurrences'] ) ? (int) $rule['min_occurrences'] : 1;

        // Single-event check; progress is binary for now
        return array( 'passed' => $in_window && $min_occurrences <= 1, 'progress' => $in_window ? 1 : 0 );
    }

    private static function evaluate_payload_compare_rule( $rule, $payload ) {
        $field       = isset( $rule['field'] ) ? $rule['field'] : null;
        $other_field = isset( $rule['other_field'] ) ? $rule['other_field'] : null;
        $operator    = isset( $rule['operator'] ) ? $rule['operator'] : '>=';
        $value       = isset( $rule['value'] ) ? $rule['value'] : null;

        if ( ! $field || ! array_key_exists( $field, (array) $payload ) ) {
            return array( 'passed' => false, 'progress' => 0 );
        }

        $left  = $payload[ $field ];
        $right = null !== $other_field && array_key_exists( $other_field, (array) $payload ) ? $payload[ $other_field ] : $value;
        if ( null === $right ) {
            return array( 'passed' => false, 'progress' => 0 );
        }

        $passed = self::compare_values( $left, $right, $operator );
        $progress = 0;
        if ( is_numeric( $right ) && is_numeric( $left ) && in_array( $operator, array( '>=', '>', '=' ), true ) && (float) $right > 0 ) {
            $progress = min( 1, (float) $left / (float) $right );
        } else {
            $progress = $passed ? 1 : 0;
        }

        return array( 'passed' => $passed, 'progress' => $progress );
    }

    private static function evaluate_payload_flag_rule( $rule, $payload ) {
        $field = isset( $rule['field'] ) ? $rule['field'] : null;
        $expected = array_key_exists( 'value', $rule ) ? $rule['value'] : true;
        if ( ! $field || ! array_key_exists( $field, (array) $payload ) ) {
            return array( 'passed' => false, 'progress' => 0 );
        }
        $passed = $payload[ $field ] === $expected;
        return array( 'passed' => $passed, 'progress' => $passed ? 1 : 0 );
    }

    private static function compare_values( $left, $right, $operator ) {
        switch ( $operator ) {
            case '>=':
                return $left >= $right;
            case '>':
                return $left > $right;
            case '=':
            case '==':
                return $left == $right; // phpcs:ignore loose comparison for portability
            case '<=':
                return $left <= $right;
            case '<':
                return $left < $right;
            default:
                return false;
        }
    }

    /**
     * Convenience helpers for legacy wrappers
     */
    public static function get_user_earned_badges( $user_id ) {
        $badges = self::get_user_badges( $user_id );
        return array_values( array_map( function( $badge ) {
            return $badge['id'];
        }, array_filter( $badges, function( $badge ) {
            return ! empty( $badge['earned'] );
        } ) ) );
    }
}
