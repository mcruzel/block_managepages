<?php
/**
 * Output renderable for block_managepages
 *
 * @package     block_managepages
 * @copyright   2025 Maxime Cruzel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_managepages\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;

class main implements renderable, templatable {
    private $courseid;
    
    public function __construct($courseid) {
        $this->courseid = $courseid;
    }    public function export_for_template(renderer_base $output) {
        global $PAGE, $SCRIPT, $DB;
        $modinfo = get_fast_modinfo($this->courseid);
        $sections = $modinfo->get_section_info_all();
        $arbo = array();
        $context = \context_course::instance($this->courseid);
        $canedit = has_capability('moodle/course:manageactivities', $context);
        
        // Détection si on est sur une page de section
        $issectionpage = false;
        $currentsectionnum = null;
        
        if (strpos($SCRIPT, '/course/section.php') !== false) {
            $issectionpage = true;
            // Chercher le paramètre 'section' dans l'URL
            $sectionnum = optional_param('section', null, PARAM_INT);
            if ($sectionnum !== null) {
                $currentsectionnum = $sectionnum;
            }
        }

        // Construire la structure des sections
        foreach ($sections as $section) {
            $sectionnum = $section->section;
            
            // Si on est sur /course/section.php, afficher uniquement la section courante
            if ($issectionpage && $currentsectionnum !== null && $sectionnum !== $currentsectionnum) {
                continue;
            }
            
            if (empty($section->name) && $sectionnum == 0) {
                $sectionname = get_string('section0name', 'format_topics');
            } else if (empty($section->name)) {
                $sectionname = get_string('section', 'moodle', $sectionnum);
            } else {
                $sectionname = format_string($section->name);
            }
            $arbo[$sectionnum] = [
                'name' => $sectionname,
                'pages' => [],
                'sectionnum' => $sectionnum
            ];        }
        
        // Ajouter les pages dans les sections appropriées
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname === 'page' && $cm->uservisible) {
                $sectionnum = $cm->sectionnum;
                
                // Si on est sur /course/section.php, traiter uniquement les pages de la section courante
                if ($issectionpage && $currentsectionnum !== null && $sectionnum !== $currentsectionnum) {
                    continue;
                }
                
                if (!isset($arbo[$sectionnum])) {
                    $arbo[$sectionnum] = [
                        'name' => get_string('section', 'moodle', $sectionnum),
                        'pages' => [],
                        'sectionnum' => $sectionnum
                    ];
                }
                $arbo[$sectionnum]['pages'][] = [
                    'id' => $cm->instance,
                    'name' => format_string($cm->name),
                    'downloadurl' => new \moodle_url('/blocks/managepages/export.php', [
                        'courseid' => $this->courseid,
                        'page_ids[]' => $cm->instance
                    ]),
                    'editid' => $cm->instance,
                    'canedit' => $canedit
                ];
            }
        }

        return [
            'sections' => $arbo,
            'exporturl' => new \moodle_url('/blocks/managepages/export.php', ['courseid' => $this->courseid]),
            'sesskey' => sesskey(),
            'courseid' => $this->courseid,
            'issectionpage' => $issectionpage,
            'currentsectionnum' => $currentsectionnum
        ];
    }
}
