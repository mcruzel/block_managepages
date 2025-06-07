<?php
/**
 * Privacy provider for block_managepages
 *
 * @package     block_managepages
 * @copyright   2025 Maxime Cruzel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_managepages\privacy;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\null_provider {
    public static function get_reason() {
        return get_string('privacy:metadata', 'block_managepages');
    }
}
