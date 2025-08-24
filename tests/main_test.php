<?php
/**
 * Unit tests for main output class
 *
 * @package     block_managepages
 * @copyright   2025 Maxime Cruzel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group       block_managepages
 */

namespace block_managepages\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for main output functionality
 */
class main_test extends \advanced_testcase {

    /**
     * Set up for each test
     */
    protected function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test main class instantiation
     */
    public function test_main_creation() {
        $course = $this->getDataGenerator()->create_course();
        $main = new \block_managepages\output\main($course->id);
        
        $this->assertInstanceOf('\block_managepages\output\main', $main);
    }

    /**
     * Test export for template on course view page
     */
    public function test_export_for_template_course_view() {
        global $PAGE, $SCRIPT;
        
        // Mock course view page
        $course = $this->getDataGenerator()->create_course();
        $SCRIPT = '/course/view.php';
        
        // Create test pages in different sections
        $page1 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Page 1',
            'content' => '<p>Content 1</p>',
            'section' => 0
        ]);
        
        $page2 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Page 2',
            'content' => '<p>Content 2</p>',
            'section' => 1
        ]);
        
        $page3 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Page 3',
            'content' => '<p>Content 3</p>',
            'section' => 1
        ]);
        
        // Set up page context
        $PAGE->set_course($course);
        $PAGE->set_context(\context_course::instance($course->id));
        
        $main = new \block_managepages\output\main($course->id);
        $data = $main->export_for_template(new \core\output\renderer_base(new \moodle_page(), null));
        
        // Test that all sections and pages are included
        $this->assertArrayHasKey('sections', $data);
        $this->assertArrayHasKey('exporturl', $data);
        $this->assertArrayHasKey('sesskey', $data);
        $this->assertArrayHasKey('courseid', $data);
        
        // Should have sections with pages
        $sections = $data['sections'];
        $this->assertGreaterThan(0, count($sections));
        
        // Find pages in sections
        $all_pages = [];
        foreach ($sections as $section) {
            $all_pages = array_merge($all_pages, $section['pages']);
        }
        
        $this->assertCount(3, $all_pages);
        
        // Verify page names
        $page_names = array_column($all_pages, 'name');
        $this->assertContains('Page 1', $page_names);
        $this->assertContains('Page 2', $page_names);
        $this->assertContains('Page 3', $page_names);
    }

    /**
     * Test export for template on section page
     */
    public function test_export_for_template_section_page() {
        global $PAGE, $SCRIPT;
        
        // Mock section page
        $course = $this->getDataGenerator()->create_course();
        $SCRIPT = '/course/section.php';
        
        // Create test pages in different sections
        $page1 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Page 1 Section 0',
            'content' => '<p>Content 1</p>',
            'section' => 0
        ]);
        
        $page2 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Page 2 Section 1',
            'content' => '<p>Content 2</p>',
            'section' => 1
        ]);
        
        $page3 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Page 3 Section 1',
            'content' => '<p>Content 3</p>',
            'section' => 1
        ]);
        
        // Set up page context
        $PAGE->set_course($course);
        $PAGE->set_context(\context_course::instance($course->id));
        
        // Mock section parameter (simulate being on section 1)
        $_GET['section'] = 1;
        
        $main = new \block_managepages\output\main($course->id);
        $data = $main->export_for_template(new \core\output\renderer_base(new \moodle_page(), null));
        
        // Should only show pages from section 1
        $sections = $data['sections'];
        
        // Find all pages
        $all_pages = [];
        foreach ($sections as $section) {
            $all_pages = array_merge($all_pages, $section['pages']);
        }
        
        // Should only have pages from section 1
        $this->assertCount(2, $all_pages);
        
        $page_names = array_column($all_pages, 'name');
        $this->assertContains('Page 2 Section 1', $page_names);
        $this->assertContains('Page 3 Section 1', $page_names);
        $this->assertNotContains('Page 1 Section 0', $page_names);
        
        // Clean up
        unset($_GET['section']);
    }

    /**
     * Test edit capabilities in export data
     */
    public function test_export_for_template_edit_capabilities() {
        global $PAGE;
        
        // Create course and users
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        
        // Enrol users
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        
        // Create test page
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Test Page',
            'content' => '<p>Test content</p>'
        ]);
        
        // Set up page context
        $PAGE->set_course($course);
        $PAGE->set_context(\context_course::instance($course->id));
        
        // Test as teacher (should have edit capability)
        $this->setUser($teacher);
        $main = new \block_managepages\output\main($course->id);
        $data = $main->export_for_template(new \core\output\renderer_base(new \moodle_page(), null));
        
        // Find the page in the data
        $all_pages = [];
        foreach ($data['sections'] as $section) {
            $all_pages = array_merge($all_pages, $section['pages']);
        }
        
        $this->assertCount(1, $all_pages);
        $this->assertTrue($all_pages[0]['canedit']);
        $this->assertArrayHasKey('editid', $all_pages[0]);
        $this->assertArrayHasKey('downloadurl', $all_pages[0]);
        
        // Test as student (should NOT have edit capability)
        $this->setUser($student);
        $main = new \block_managepages\output\main($course->id);
        $data = $main->export_for_template(new \core\output\renderer_base(new \moodle_page(), null));
        
        // Find the page in the data
        $all_pages = [];
        foreach ($data['sections'] as $section) {
            $all_pages = array_merge($all_pages, $section['pages']);
        }
        
        $this->assertCount(1, $all_pages);
        $this->assertFalse($all_pages[0]['canedit']);
        $this->assertArrayHasKey('downloadurl', $all_pages[0]);
    }

    /**
     * Test with course containing no pages
     */
    public function test_export_for_template_no_pages() {
        global $PAGE;
        
        $course = $this->getDataGenerator()->create_course();
        
        // Set up page context
        $PAGE->set_course($course);
        $PAGE->set_context(\context_course::instance($course->id));
        
        $main = new \block_managepages\output\main($course->id);
        $data = $main->export_for_template(new \core\output\renderer_base(new \moodle_page(), null));
        
        // Should still return valid data structure
        $this->assertArrayHasKey('sections', $data);
        $this->assertArrayHasKey('exporturl', $data);
        $this->assertArrayHasKey('sesskey', $data);
        $this->assertArrayHasKey('courseid', $data);
        
        // Sections might be empty or contain empty page arrays
        $all_pages = [];
        foreach ($data['sections'] as $section) {
            $all_pages = array_merge($all_pages, $section['pages']);
        }
        
        $this->assertCount(0, $all_pages);
    }

    /**
     * Test data structure validation
     */
    public function test_export_data_structure() {
        global $PAGE;
        
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Test Page',
            'content' => '<p>Test content</p>'
        ]);
        
        // Set up page context
        $PAGE->set_course($course);
        $PAGE->set_context(\context_course::instance($course->id));
        
        $main = new \block_managepages\output\main($course->id);
        $data = $main->export_for_template(new \core\output\renderer_base(new \moodle_page(), null));
        
        // Validate main structure
        $required_keys = ['sections', 'exporturl', 'sesskey', 'courseid'];
        foreach ($required_keys as $key) {
            $this->assertArrayHasKey($key, $data);
        }
        
        // Validate course ID
        $this->assertEquals($course->id, $data['courseid']);
        
        // Validate sections structure
        $this->assertIsArray($data['sections']);
        
        foreach ($data['sections'] as $section) {
            $this->assertArrayHasKey('name', $section);
            $this->assertArrayHasKey('pages', $section);
            $this->assertIsArray($section['pages']);
            
            foreach ($section['pages'] as $page_data) {
                $this->assertArrayHasKey('id', $page_data);
                $this->assertArrayHasKey('name', $page_data);
                $this->assertArrayHasKey('canedit', $page_data);
                $this->assertIsBool($page_data['canedit']);
            }
        }
    }
}
