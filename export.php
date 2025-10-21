<?php
/**
 * Export handler for block_managepages
 *
 * @package     block_managepages
 * @copyright   2025 Maxime Cruzel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || require_once(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

$courseid = required_param('courseid', PARAM_INT);
$ajaxmode = optional_param('ajax', 0, PARAM_INT);

// Verify course exists and user has access
try {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    require_capability('block/managepages:export', $context);
} catch (Exception $e) {
    if ($ajaxmode > 0) {
        send_json_error('Invalid course or insufficient permissions', 403);
    }
    throw new moodle_exception('invalidcourse', 'error');
}

$exporter = new \block_managepages\exporter();

// Route to appropriate handler based on AJAX mode
switch ($ajaxmode) {
    case 1:
        handle_clipboard_export($exporter, $courseid);
        break;
    case 2:
        handle_get_page_content($exporter, $courseid);
        break;
    case 3:
        handle_save_page_content($courseid);
        break;
    default:
        handle_file_export($exporter, $courseid);
        break;
}

/**
 * Handle AJAX request to get markdown for clipboard.
 *
 * @param \block_managepages\exporter $exporter
 * @param int $courseid
 */
function handle_clipboard_export($exporter, $courseid) {
    $pageids = optional_param_array('page_ids', [], PARAM_INT);

    if (empty($pageids)) {
        send_json_error(get_string('error:nopagesselected', 'block_managepages'), 400);
    }

    try {
        $pages = $exporter->fetch_selected_pages($pageids, $courseid);

        if (empty($pages)) {
            send_json_error('No accessible pages found', 404);
        }

        $markdown = $exporter->get_structured_markdown($pages, $courseid);
        send_json_response(['markdown' => $markdown]);
    } catch (Exception $e) {
        debugging('Error in clipboard export: ' . $e->getMessage(), DEBUG_DEVELOPER);
        send_json_error('Error generating markdown content', 500);
    }
}

/**
 * Handle AJAX request to get page content for editing.
 *
 * @param \block_managepages\exporter $exporter
 * @param int $courseid
 */
function handle_get_page_content($exporter, $courseid) {
    global $DB;

    $pageid = required_param('pageid', PARAM_INT);

    try {
        // Verify page exists
        $page = $DB->get_record('page', ['id' => $pageid], 'id, name, course', MUST_EXIST);

        // Verify page belongs to this course
        if ($page->course != $courseid) {
            send_json_error('Page does not belong to this course', 403);
        }

        // Get page content
        $content = $exporter->get_page_content($pageid);

        // Get page name from course module
        $modinfo = get_fast_modinfo($courseid);
        $name = '';

        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname === 'page' && $cm->instance == $pageid && $cm->uservisible) {
                $name = format_string($cm->name);
                break;
            }
        }

        if (empty($name)) {
            send_json_error('Page not found or not accessible', 404);
        }

        send_json_response([
            'content' => $content,
            'name' => $name
        ]);
    } catch (dml_missing_record_exception $e) {
        send_json_error('Page not found', 404);
    } catch (Exception $e) {
        debugging('Error retrieving page content: ' . $e->getMessage(), DEBUG_DEVELOPER);
        send_json_error('Error loading page content', 500);
    }
}

/**
 * Handle AJAX request to save page content.
 *
 * @param int $courseid
 */
function handle_save_page_content($courseid) {
    global $DB;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_error('Invalid request method', 405);
    }

    $pageid = required_param('pageid', PARAM_INT);
    $content = required_param('content', PARAM_RAW);

    try {
        // Verify page exists and belongs to course
        $page = $DB->get_record('page', ['id' => $pageid], 'id, course', MUST_EXIST);

        if ($page->course != $courseid) {
            send_json_error('Page does not belong to this course', 403);
        }

        // Additional capability check for editing
        $context = context_course::instance($courseid);
        require_capability('moodle/course:manageactivities', $context);

        // Update page content
        $updaterecord = new stdClass();
        $updaterecord->id = $pageid;
        $updaterecord->content = $content;
        $updaterecord->timemodified = time();

        $DB->update_record('page', $updaterecord);

        // Trigger course module updated event
        $cm = get_coursemodule_from_instance('page', $pageid, $courseid);
        if ($cm) {
            \core\event\course_module_updated::create_from_cm($cm)->trigger();
        }

        send_json_response(['success' => true]);
    } catch (dml_missing_record_exception $e) {
        send_json_error('Page not found', 404);
    } catch (required_capability_exception $e) {
        send_json_error('Insufficient permissions to edit page', 403);
    } catch (Exception $e) {
        debugging('Error saving page content: ' . $e->getMessage(), DEBUG_DEVELOPER);
        send_json_error('Error saving page content', 500);
    }
}

/**
 * Handle regular file export (download).
 *
 * @param \block_managepages\exporter $exporter
 * @param int $courseid
 */
function handle_file_export($exporter, $courseid) {
    $pageids = optional_param_array('page_ids', [], PARAM_INT);

    if (empty($pageids)) {
        throw new moodle_exception('error:nopagesselected', 'block_managepages');
    }

    try {
        $pages = $exporter->fetch_selected_pages($pageids, $courseid);

        if (empty($pages)) {
            throw new moodle_exception('error:nopagesselected', 'block_managepages');
        }

        if (count($pages) === 1) {
            // Single file export
            $markdown = $exporter->convert_to_markdown($pages);
            $filename = $exporter->get_clean_filename($pages[0]->name) . '.md';

            // Security: Ensure filename is safe
            $filename = clean_filename($filename);

            header('Content-Type: text/markdown; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($markdown));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');

            echo $markdown;
        } else {
            // Multiple files - create ZIP
            $markdown_files = [];

            foreach ($pages as $page) {
                $sectionpath = $exporter->get_page_section_path($page);
                $filename = ($sectionpath ? $sectionpath . '/' : '');
                $filename .= $exporter->get_clean_filename($page->name) . '.md';
                $markdown_files[$filename] = $exporter->convert_to_markdown([$page]);
            }

            $zipfile = $exporter->create_zip($markdown_files);

            if (!$zipfile || !file_exists($zipfile)) {
                throw new moodle_exception('error:zipcreationfailed', 'block_managepages');
            }

            $zipfilename = 'pages_markdown_' . date('Y-m-d') . '.zip';

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipfilename . '"');
            header('Content-Length: ' . filesize($zipfile));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');

            readfile($zipfile);
            unlink($zipfile);
        }

        exit;
    } catch (Exception $e) {
        debugging('Error in file export: ' . $e->getMessage(), DEBUG_DEVELOPER);
        throw new moodle_exception('error:zipcreationfailed', 'block_managepages');
    }
}

/**
 * Send JSON response and exit.
 *
 * @param array $data
 */
function send_json_response($data) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode($data);
    exit;
}

/**
 * Send JSON error response and exit.
 *
 * @param string $message
 * @param int $code HTTP response code
 */
function send_json_error($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode(['error' => $message]);
    exit;
}
