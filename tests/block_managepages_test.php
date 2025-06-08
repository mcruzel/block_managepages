<?php
/**
 * Unit tests for block_managepages
 *
 * @package     block_managepages
 * @copyright   2025 Maxime Cruzel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group       block_managepages
 */

namespace block_managepages;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/managepages/block_managepages.php');

/**
 * Tests for block_managepages functionality
 */
class block_managepages_test extends \advanced_testcase {

    /**
     * Set up for each test
     */
    protected function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test block creation and basic functionality
     */
    public function test_block_creation() {
        // Create test course
        $course = $this->getDataGenerator()->create_course();
        
        // Create block instance
        $block = new \block_managepages();
        $block->init();
        
        // Test that block can be instantiated
        $this->assertInstanceOf('block_managepages', $block);
        $this->assertEquals(get_string('pluginname', 'block_managepages'), $block->title);
    }

    /**
     * Test block applicable formats
     */
    public function test_applicable_formats() {
        $block = new \block_managepages();
        $formats = $block->applicable_formats();
        
        // Should be available on course pages
        $this->assertTrue($formats['course-view']);
        $this->assertTrue($formats['course']);
        
        // Should NOT be available on other pages
        $this->assertFalse($formats['site-index']);
        $this->assertFalse($formats['my']);
        $this->assertFalse($formats['mod']);
        $this->assertFalse($formats['all']);
    }

    /**
     * Test block permissions
     */
    public function test_block_permissions() {
        // Create test course and users
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        
        // Enrol users with different roles
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        
        $context = \context_course::instance($course->id);
        
        // Test teacher has export capability
        $this->setUser($teacher);
        $this->assertTrue(has_capability('block/managepages:export', $context));
        
        // Test student does NOT have export capability
        $this->setUser($student);
        $this->assertFalse(has_capability('block/managepages:export', $context));
    }

    /**
     * Test block content generation
     */
    public function test_block_content() {
        // Create test course with pages
        $course = $this->getDataGenerator()->create_course();
        $this->setCurrentCourse($course);
        
        // Create some test pages
        $page1 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Test Page 1',
            'content' => '<p>Content of page 1</p>'
        ]);
        
        $page2 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Test Page 2',
            'content' => '<p>Content of page 2</p>'
        ]);
        
        // Create block and get content
        $block = new \block_managepages();
        $block->init();
        
        // Mock the course global
        global $COURSE;
        $COURSE = $course;
        
        $content = $block->get_content();
        
        // Test that content contains expected elements
        $this->assertNotNull($content);
        $this->assertNotNull($content->text);
        $this->assertStringContainsString('Test Page 1', $content->text);
        $this->assertStringContainsString('Test Page 2', $content->text);
    }

    /**
     * Test block with no pages in course
     */
    public function test_block_content_no_pages() {
        // Create test course without pages
        $course = $this->getDataGenerator()->create_course();
        $this->setCurrentCourse($course);
        
        // Create block and get content
        $block = new \block_managepages();
        $block->init();
        
        global $COURSE;
        $COURSE = $course;
        
        $content = $block->get_content();
        
        // Should still return content even with no pages
        $this->assertNotNull($content);
        $this->assertNotNull($content->text);
    }

    /**
     * Helper method to set current course
     */
    private function setCurrentCourse($course) {
        global $PAGE;
        $PAGE->set_course($course);
        $PAGE->set_context(\context_course::instance($course->id));
    }
}
