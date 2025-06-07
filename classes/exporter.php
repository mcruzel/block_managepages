<?php
/**
 * Fonctions d'export des pages de type "page" pour block_managepages.
 *
 * @package     block_managepages
 * @category    block
 * @copyright   2025 Maxime Cruzel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_managepages;

use ZipArchive;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Classe utilitaire pour l'export des pages.
 *
 * @package block_managepages
 */
class exporter {
    /**
     * Récupère les pages sélectionnées et visibles par l'utilisateur.
     *
     * @param int[] $selected_ids
     * @param int $courseid
     * @return object[]
     */
    public function fetch_selected_pages($selected_ids, $courseid) {
        $modinfo = get_fast_modinfo($courseid);
        $pages = [];
        foreach ($modinfo->get_cms() as $cm) {
            // On ne prend que les pages visibles à l'utilisateur.
            if ($cm->modname === 'page' && $cm->uservisible && in_array($cm->instance, $selected_ids)) {
                $page = (object)[
                    'id' => $cm->instance,
                    'name' => $cm->name,
                    'course' => $courseid,
                    'content' => $this->get_page_content($cm->instance)
                ];
                $pages[] = $page;
            }
        }
        return $pages;
    }

    /**
     * Récupère le contenu d'une page.
     *
     * @param int $pageid
     * @return string
     */
    public function get_page_content($pageid) {
        global $DB;
        $record = $DB->get_record('page', ['id' => $pageid], 'content', MUST_EXIST);
        return $record->content;
    }

    /**
     * Convertit une ou plusieurs pages en markdown.
     *
     * @param object[] $pages
     * @return string
     */
    public function convert_to_markdown($pages) {
        $markdown_content = '';
        foreach ($pages as $page) {
            $markdown_content .= "# " . $page->name . "\n\n";
            $markdown_content .= $page->content . "\n\n";
        }
        return $markdown_content;
    }

    /**
     * Crée un fichier ZIP à partir d'un tableau de fichiers markdown.
     *
     * @param array $markdown_files
     * @return string|false Chemin du zip ou false
     */
    public function create_zip($markdown_files) {
        $zip = new ZipArchive();
        $zip_filename = tempnam(sys_get_temp_dir(), 'export_pages_') . '.zip';
        if ($zip->open($zip_filename, ZipArchive::CREATE) !== TRUE) {
            return false;
        }
        foreach ($markdown_files as $filename => $content) {
            $zip->addFromString($filename, $content);
        }
        $zip->close();
        return $zip_filename;
    }

    /**
     * Retourne le chemin de la section pour une page (pour organiser le zip).
     *
     * @param object $page
     * @return string
     */
    public function get_page_section_path($page) {
        global $DB;
        $sectionname = '';
        $cm = get_coursemodule_from_instance('page', $page->id, $page->course);
        if ($cm && isset($cm->section)) {
            $section = $DB->get_record('course_sections', ['id' => $cm->section], 'id, name, section');
            if ($section) {
                $sectionname = $section->name ? $section->name : 'section_' . $section->section;
            }
        }
        $sectionname = trim($sectionname);
        $sectionname = str_replace(' ', '_', $sectionname);
        $sectionname = preg_replace('/[^A-Za-z0-9_\-]/', '', $sectionname);
        return $sectionname;
    }

    /**
     * Nettoie le nom d'un fichier pour le système de fichiers.
     *
     * @param string $name
     * @return string
     */
    public function get_clean_filename($name) {
        $name = trim($name);
        $name = str_replace(' ', '_', $name);
        $name = preg_replace('/[^A-Za-z0-9_\-]/', '', $name);
        return $name;
    }

    /**
     * Génère le markdown structuré pour le presse-papier.
     *
     * @param object[] $pages
     * @param int $courseid
     * @return string
     */
    public function get_structured_markdown($pages, $courseid) {
        global $DB;
        $sections = $DB->get_records('course_sections', array('course' => $courseid), 'section ASC');
        $sectionmap = [];
        foreach ($sections as $section) {
            $sectionmap[$section->id] = $section;
        }
        $result = [];
        $lastsection = null;
        foreach ($pages as $page) {
            $cm = get_coursemodule_from_instance('page', $page->id, $courseid);
            $section = isset($sectionmap[$cm->section]) ? $sectionmap[$cm->section] : null;
            $sectionname = $section ? format_string($section->name) : '';
            $sectionname = $sectionname ?: 'Section ' . ($section ? $section->section : '?');
            $md = '';
            if ($sectionname !== $lastsection) {
                $md .= "# $sectionname\n";
                $lastsection = $sectionname;
            }
            $md .= "## " . format_string($page->name) . "\n\n" . $page->content . "\n\n";
            $result[] = $md;
        }
        return implode("\n---\n", $result);
    }
}
?>