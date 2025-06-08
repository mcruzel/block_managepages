<?php
/**
 * Upgrade script for block_managepages
 *
 * @package     block_managepages
 * @copyright   2025 Maxime Cruzel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_managepages_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023100100) {
        // Premier point de mise à jour (aucune action nécessaire).
        upgrade_block_savepoint(true, 2023100100, 'managepages');
    }

    if ($oldversion < 2025060700) {
        // Version 1.1.0 - Ajout des fonctionnalités d'édition.
        upgrade_block_savepoint(true, 2025060700, 'managepages');
    }    if ($oldversion < 2025060801) {
        // Version 1.2.0 - Tests unitaires et améliorations.
        // Aucun changement de structure de base de données nécessaire.
        upgrade_block_savepoint(true, 2025060801, 'managepages');
    }    if ($oldversion < 2025060802) {
        // Version 1.2.1 - Correction installation.
        // Aucun changement de structure de base de données nécessaire.
        upgrade_block_savepoint(true, 2025060802, 'managepages');
    }

    if ($oldversion < 2025060805) {
        // Version 1.2.2 - Correction définitive installation.
        // Aucun changement de structure de base de données nécessaire.
        upgrade_block_savepoint(true, 2025060805, 'managepages');
    }

    return true;
}
