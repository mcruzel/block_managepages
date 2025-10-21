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
     * Maximum number of pages that can be exported at once.
     */
    const MAX_PAGES = 100;

    /**
     * Maximum filename length.
     */
    const MAX_FILENAME_LENGTH = 200;

    /**
     * Récupère les pages sélectionnées et visibles par l'utilisateur.
     *
     * @param int[] $selected_ids Array of page IDs
     * @param int $courseid Course ID
     * @return object[] Array of page objects
     * @throws invalid_parameter_exception
     */
    public function fetch_selected_pages($selected_ids, $courseid) {
        global $DB;

        // Validate parameters
        if (!is_array($selected_ids) || empty($selected_ids)) {
            throw new invalid_parameter_exception('Page IDs must be a non-empty array');
        }

        if (!is_number($courseid) || $courseid <= 0) {
            throw new invalid_parameter_exception('Invalid course ID');
        }

        // Limit number of pages to prevent resource exhaustion
        if (count($selected_ids) > self::MAX_PAGES) {
            throw new invalid_parameter_exception('Too many pages selected. Maximum is ' . self::MAX_PAGES);
        }

        // Sanitize IDs
        $selected_ids = array_map('intval', $selected_ids);
        $selected_ids = array_unique($selected_ids);
        $selected_ids = array_filter($selected_ids, function($id) {
            return $id > 0;
        });

        if (empty($selected_ids)) {
            return [];
        }

        // Verify course exists
        $course = $DB->get_record('course', ['id' => $courseid], 'id', IGNORE_MISSING);
        if (!$course) {
            throw new invalid_parameter_exception('Course not found');
        }

        $modinfo = get_fast_modinfo($courseid);
        $pages = [];

        foreach ($modinfo->get_cms() as $cm) {
            // Only include pages that are visible to the user and selected
            if ($cm->modname === 'page' && $cm->uservisible && in_array($cm->instance, $selected_ids)) {
                try {
                    $content = $this->get_page_content($cm->instance);

                    $page = (object)[
                        'id' => $cm->instance,
                        'name' => format_string($cm->name),
                        'course' => $courseid,
                        'content' => $content,
                        'section' => $cm->section
                    ];
                    $pages[] = $page;
                } catch (Exception $e) {
                    // Log error but continue with other pages
                    debugging('Error fetching page ' . $cm->instance . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                    continue;
                }
            }
        }

        return $pages;
    }

    /**
     * Récupère le contenu d'une page.
     *
     * @param int $pageid Page ID
     * @return string Page content
     * @throws dml_missing_record_exception If page not found
     * @throws invalid_parameter_exception If invalid page ID
     */
    public function get_page_content($pageid) {
        global $DB;

        if (!is_number($pageid) || $pageid <= 0) {
            throw new invalid_parameter_exception('Invalid page ID');
        }

        $record = $DB->get_record('page', ['id' => $pageid], 'content', MUST_EXIST);

        // Return content, ensuring it's a string
        return is_string($record->content) ? $record->content : '';
    }

    /**
     * Convertit une ou plusieurs pages en markdown.
     *
     * @param object[] $pages Array of page objects
     * @return string Markdown content
     * @throws invalid_parameter_exception If pages array is invalid
     */
    public function convert_to_markdown($pages) {
        if (!is_array($pages) || empty($pages)) {
            throw new invalid_parameter_exception('Pages must be a non-empty array');
        }

        $markdown_content = '';

        foreach ($pages as $page) {
            if (!isset($page->name) || !isset($page->content)) {
                debugging('Invalid page object, skipping', DEBUG_DEVELOPER);
                continue;
            }

            // Add page title as heading
            $markdown_content .= "# " . $page->name . "\n\n";

            // Add page content (HTML for now, could be converted to markdown)
            $markdown_content .= $page->content . "\n\n";
        }

        return $markdown_content;
    }

    /**
     * Crée un fichier ZIP à partir d'un tableau de fichiers markdown.
     *
     * @param array $markdown_files Associative array of filename => content
     * @return string|false Path to created ZIP file or false on error
     * @throws coding_exception If ZIP creation fails
     */
    public function create_zip($markdown_files) {
        if (!is_array($markdown_files) || empty($markdown_files)) {
            throw new coding_exception('Markdown files must be a non-empty array');
        }

        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            throw new coding_exception('ZipArchive class not available');
        }

        $zip = new ZipArchive();
        $zip_filename = tempnam(sys_get_temp_dir(), 'export_pages_') . '.zip';

        if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            debugging('Failed to create ZIP file: ' . $zip_filename, DEBUG_DEVELOPER);
            return false;
        }

        foreach ($markdown_files as $filename => $content) {
            // Ensure filename is safe
            $safe_filename = clean_param($filename, PARAM_PATH);

            if (empty($safe_filename)) {
                debugging('Invalid filename: ' . $filename, DEBUG_DEVELOPER);
                continue;
            }

            // Limit filename length
            if (strlen($safe_filename) > self::MAX_FILENAME_LENGTH) {
                $safe_filename = substr($safe_filename, 0, self::MAX_FILENAME_LENGTH);
            }

            if (!$zip->addFromString($safe_filename, $content)) {
                debugging('Failed to add file to ZIP: ' . $safe_filename, DEBUG_DEVELOPER);
            }
        }

        $zip->close();

        // Verify ZIP was created
        if (!file_exists($zip_filename) || filesize($zip_filename) === 0) {
            if (file_exists($zip_filename)) {
                @unlink($zip_filename);
            }
            return false;
        }

        return $zip_filename;
    }

    /**
     * Retourne le chemin de la section pour une page (pour organiser le zip).
     *
     * @param object $page Page object with id and course properties
     * @return string Section path or empty string
     */
    public function get_page_section_path($page) {
        global $DB;

        if (!isset($page->id) || !isset($page->course)) {
            return '';
        }

        try {
            $cm = get_coursemodule_from_instance('page', $page->id, $page->course);

            if (!$cm || !isset($cm->section)) {
                return '';
            }

            $section = $DB->get_record('course_sections', ['id' => $cm->section], 'id, name, section');

            if (!$section) {
                return '';
            }

            // Use section name if available, otherwise use section number
            $sectionname = !empty($section->name) ? $section->name : 'section_' . $section->section;

            // Clean the section name
            return $this->get_clean_filename($sectionname);

        } catch (Exception $e) {
            debugging('Error getting section path: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return '';
        }
    }

    /**
     * Nettoie le nom d'un fichier pour le système de fichiers.
     *
     * @param string $name Filename to clean
     * @return string Cleaned filename
     */
    public function get_clean_filename($name) {
        if (!is_string($name) || trim($name) === '') {
            return 'unnamed';
        }

        $name = trim($name);

        // Remove HTML tags
        $name = strip_tags($name);

        // Replace spaces with underscores
        $name = str_replace(' ', '_', $name);

        // Remove any character that's not alphanumeric, underscore, or hyphen
        $name = preg_replace('/[^A-Za-z0-9_\-]/', '', $name);

        // Remove multiple consecutive underscores or hyphens
        $name = preg_replace('/[_-]+/', '_', $name);

        // Trim underscores and hyphens from ends
        $name = trim($name, '_-');

        // If name is empty after cleaning, use default
        if (empty($name)) {
            $name = 'unnamed';
        }

        // Limit length
        if (strlen($name) > self::MAX_FILENAME_LENGTH) {
            $name = substr($name, 0, self::MAX_FILENAME_LENGTH);
        }

        return $name;
    }

    /**
     * Génère le markdown structuré pour le presse-papier.
     *
     * @param object[] $pages Array of page objects
     * @param int $courseid Course ID
     * @return string Structured markdown content
     * @throws invalid_parameter_exception If parameters are invalid
     */
    public function get_structured_markdown($pages, $courseid) {
        global $DB;

        if (!is_array($pages) || empty($pages)) {
            throw new invalid_parameter_exception('Pages must be a non-empty array');
        }

        if (!is_number($courseid) || $courseid <= 0) {
            throw new invalid_parameter_exception('Invalid course ID');
        }

        // Get all sections for the course
        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');
        $sectionmap = [];

        foreach ($sections as $section) {
            $sectionmap[$section->id] = $section;
        }

        $result = [];
        $lastsection = null;

        foreach ($pages as $page) {
            if (!isset($page->id) || !isset($page->name) || !isset($page->content)) {
                debugging('Invalid page object, skipping', DEBUG_DEVELOPER);
                continue;
            }

            try {
                $cm = get_coursemodule_from_instance('page', $page->id, $courseid);

                if (!$cm) {
                    debugging('Course module not found for page ' . $page->id, DEBUG_DEVELOPER);
                    continue;
                }

                $section = isset($sectionmap[$cm->section]) ? $sectionmap[$cm->section] : null;
                $sectionname = '';

                if ($section) {
                    $sectionname = !empty($section->name) ? format_string($section->name) : 'Section ' . $section->section;
                } else {
                    $sectionname = 'Section unknown';
                }

                $md = '';

                // Add section header if it changed
                if ($sectionname !== $lastsection) {
                    $md .= "# " . $sectionname . "\n\n";
                    $lastsection = $sectionname;
                }

                // Add page as subsection
                $md .= "## " . format_string($page->name) . "\n\n";
                $md .= $page->content . "\n\n";

                $result[] = $md;

            } catch (Exception $e) {
                debugging('Error processing page ' . $page->id . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                continue;
            }
        }

        return implode("\n---\n\n", $result);
    }
}
