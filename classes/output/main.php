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
    }
    public function export_for_template(renderer_base $output) {
        $modinfo = get_fast_modinfo($this->courseid);
        $sections = $modinfo->get_section_info_all();
        $arbo = array();
        foreach ($sections as $section) {
            if (empty($section->name) && $section->section == 0) {
                $sectionname = get_string('section0name', 'format_topics');
            } else {
                $sectionname = format_string($section->name);
            }
            $sectionkey = str_replace(' ', '_', $sectionname);
            $arbo[$sectionkey] = [
                'name' => $sectionname,
                'pages' => []
            ];
        }
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname === 'page' && $cm->uservisible) {
                $sectionidx = $cm->sectionnum;
                $sectionkeys = array_keys($arbo);
                if (isset($sectionkeys[$sectionidx])) {
                    $arbo[$sectionkeys[$sectionidx]]['pages'][] = [
                        'id' => $cm->instance,
                        'name' => format_string($cm->name),
                        'canedit' => true // Pour usage futur si besoin
                    ];
                }
            }
        }
        return [
            'sections' => array_values($arbo),
            'exporturl' => new \moodle_url('/blocks/managepages/export.php', ['courseid' => $this->courseid]),
            'sesskey' => sesskey()
        ];
    }
}
?>