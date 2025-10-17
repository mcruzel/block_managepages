<?php
/**
 * Bloc de gestion et d'export des pages de type "page" dans un cours Moodle.
 *
 * @package     block_managepages
 * @category    block
 * @copyright   2025 Maxime Cruzel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Bloc principal pour la gestion et l'export des pages.
 *
 * @package block_managepages
 */
class block_managepages extends block_base {
    /**
     * Initialise le titre du bloc.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_managepages');
    }

    /**
     * Retourne le contenu du bloc.
     *
     * @return stdClass
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass();
        $this->content->text = $this->render_export_form();
        $this->content->footer = '';
        return $this->content;
    }

    /**
     * Génère le formulaire d'export.
     *
     * @return string
     */
    private function render_export_form() {
        global $OUTPUT;
        $courseid = $this->resolve_courseid();
        $renderable = new \block_managepages\output\main($courseid);
        $template = 'block_managepages/block_managepages';
        return $OUTPUT->render_from_template($template, $renderable->export_for_template($OUTPUT));
    }

    /**
     * Résout l'identifiant du cours à partir du contexte ou des paramètres de la requête.
     *
     * @return int
     * @throws moodle_exception Lorsque le cours ne peut pas être déterminé.
     */
    private function resolve_courseid(): int {
        global $COURSE;

        if (!empty($COURSE) && !empty($COURSE->id)) {
            return (int) $COURSE->id;
        }

        if (!empty($this->page) && !empty($this->page->course) && !empty($this->page->course->id)) {
            return (int) $this->page->course->id;
        }

        $courseid = optional_param('courseid', 0, PARAM_INT);
        if (!$courseid) {
            $courseid = optional_param('id', 0, PARAM_INT);
        }

        if ($courseid) {
            return (int) $courseid;
        }

        throw new \moodle_exception('error:missingcourseid', 'block_managepages');
    }

    /**
     * Restreint l'ajout du bloc à la page principale du cours uniquement.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'course-view' => true, // Autorisé uniquement sur la vue principale du cours.
            'course' => false,
            'site-index'  => false,
            'my'          => false,
            'mod'         => false,
            'all'         => false
        ];
    }
}
