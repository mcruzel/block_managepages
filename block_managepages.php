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
        global $OUTPUT, $COURSE;
        $renderable = new \block_managepages\output\main($COURSE->id);
        $template = 'block_managepages/block_managepages';
        return $OUTPUT->render_from_template($template, $renderable->export_for_template($OUTPUT));
    }    /**
     * Restreint l'ajout du bloc aux pages de cours uniquement.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'course-view' => true, // Autorisé sur les pages de cours
            'course' => true, // Autorisé sur toutes les pages de cours (incluant les sections)
            'site-index'  => false,
            'my'          => false,
            'mod'         => false,
            'all'         => false
        ];
    }
}
