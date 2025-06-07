<?php
/**
 * Export handler for block_managepages
 *
 * @package     block_managepages
 * @copyright   2025 Maxime Cruzel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_login();
require_sesskey();
$courseid = required_param('courseid', PARAM_INT);

// Gestion AJAX édition page (avant toute logique d'export)
if (optional_param('ajax', 0, PARAM_INT) == 2) {
    $pageid = required_param('pageid', PARAM_INT);
    $context = context_course::instance($courseid);
    require_capability('block/managepages:export', $context);
    $exporter = new \block_managepages\exporter();
    try {
        $content = $exporter->get_page_content($pageid);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'Page not found']);
        exit;
    }
    $modinfo = get_fast_modinfo($courseid);
    $name = '';
    foreach ($modinfo->get_cms() as $cm) {
        if ($cm->modname === 'page' && $cm->instance == $pageid) {
            $name = format_string($cm->name);
            break;
        }
    }
    if ($name === '') {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'Page not found']);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['content' => $content, 'name' => $name]);
    exit;
}
if (optional_param('ajax', 0, PARAM_INT) == 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sauvegarde du contenu modifié d'une page.
    $pageid = required_param('pageid', PARAM_INT);
    $context = context_course::instance($courseid);
    require_capability('block/managepages:export', $context);
    $content = required_param('content', PARAM_RAW);
    global $DB;
    $DB->set_field('page', 'content', $content, ['id' => $pageid]);
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Gestion AJAX de l'export vers le presse-papier
if (optional_param('ajax', 0, PARAM_INT) == 1) {
    $pageids = optional_param_array('page_ids', [], PARAM_INT);
    if (empty($pageids)) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => get_string('error:nopagesselected', 'block_managepages')]);
        exit;
    }
    $context = context_course::instance($courseid);
    require_capability('block/managepages:export', $context);
    $exporter = new \block_managepages\exporter();
    $pages = $exporter->fetch_selected_pages($pageids, $courseid);
    $markdown = $exporter->get_structured_markdown($pages, $courseid);
    header('Content-Type: application/json');
    echo json_encode(['markdown' => $markdown]);
    exit;
}

// Ensuite SEULEMENT la logique d'export classique
$pageids = optional_param_array('page_ids', [], PARAM_INT);
if (empty($pageids)) {
    throw new \moodle_exception('error:nopagesselected', 'block_managepages');
}
$context = context_course::instance($courseid);
require_capability('block/managepages:export', $context);
$exporter = new \block_managepages\exporter();
$pages = $exporter->fetch_selected_pages($pageids, $courseid);

if (count($pages) === 1) {
    $markdown = $exporter->convert_to_markdown($pages);
    $filename = $exporter->get_clean_filename($pages[0]->name) . '.md';
    header('Content-Type: text/markdown');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    echo $markdown;
    exit;
} else {
    $markdown_files = [];
    foreach ($pages as $page) {
        $sectionpath = $exporter->get_page_section_path($page);
        $filename = ($sectionpath ? $sectionpath . '/' : '') . $exporter->get_clean_filename($page->name) . '.md';
        $markdown_files[$filename] = $exporter->convert_to_markdown([$page]);
    }
    $zipfile = $exporter->create_zip($markdown_files);
    if ($zipfile && file_exists($zipfile)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="pages_markdown.zip"');
        readfile($zipfile);
        unlink($zipfile);
        exit;
    } else {
        throw new \moodle_exception('error:zipcreationfailed', 'block_managepages');
    }
}
