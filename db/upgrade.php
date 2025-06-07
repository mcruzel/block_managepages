<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_block_managepages_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023100100) {
        // Premier point de mise à jour (aucune action nécessaire).
        upgrade_block_savepoint(true, 2023100100, 'managepages');
        return true;
    }
    if ($oldversion < 2025060700) {
        // Aucun changement de structure pour cette version, simple savepoint.
        upgrade_block_savepoint(true, 2025060700, 'managepages');
        return true;
    }
}
