<?php
/**
 * Unit tests for export functionality
 *
 * @package     block_managepages
 * @copyright   2025 Maxime Cruzel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group       block_managepages
 */

namespace block_managepages;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for export functionality
 */
class exporter_test extends \advanced_testcase {

    /**
     * Set up for each test
     */
    protected function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
    }    /**
     * Test exporter class instantiation
     */
    public function test_exporter_creation() {
        $exporter = new \block_managepages\exporter();
        $this->assertInstanceOf('\block_managepages\exporter', $exporter);
    }

    /**
     * Test page content retrieval
     */
    public function test_get_page_content() {
        // Create test course and page
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Test Page',
            'content' => '<h1>Test Title</h1><p>Test content with <strong>bold</strong> text.</p>'
        ]);
        
        $exporter = new \block_managepages\exporter();
        $content = $exporter->get_page_content($page->id);
        
        $this->assertStringContainsString('Test Title', $content);
        $this->assertStringContainsString('Test content', $content);
        $this->assertStringContainsString('<strong>bold</strong>', $content);
    }

    /**
     * Test fetching selected pages
     */
    public function test_fetch_selected_pages() {
        // Create test course
        $course = $this->getDataGenerator()->create_course();
        
        // Create test pages
        $page1 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Page 1',
            'content' => '<p>Content 1</p>'
        ]);
        
        $page2 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Page 2',
            'content' => '<p>Content 2</p>'
        ]);
        
        $page3 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Page 3',
            'content' => '<p>Content 3</p>'
        ]);
        
        $exporter = new \block_managepages\exporter();
        
        // Test fetching selected pages
        $selected_ids = [$page1->id, $page3->id];
        $pages = $exporter->fetch_selected_pages($selected_ids, $course->id);
        
        $this->assertCount(2, $pages);
        
        // Verify correct pages were fetched
        $page_names = array_map(function($p) { return $p->name; }, $pages);
        $this->assertContains('Page 1', $page_names);
        $this->assertContains('Page 3', $page_names);
        $this->assertNotContains('Page 2', $page_names);
    }

    /**
     * Test convert to markdown functionality
     */
    public function test_convert_to_markdown() {
        // Create test course and page
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Test Page',
            'content' => '<h1>Title</h1><p>Paragraph content</p>'
        ]);
        
        // Create page object as expected by the method
        $page_obj = (object)[
            'id' => $page->id,
            'name' => 'Test Page',
            'course' => $course->id,
            'content' => '<h1>Title</h1><p>Paragraph content</p>'
        ];
        
        $exporter = new \block_managepages\exporter();
        $markdown = $exporter->convert_to_markdown([$page_obj]);
        
        // Test that markdown contains expected content
        $this->assertStringContainsString('# Test Page', $markdown);
        $this->assertStringContainsString('<h1>Title</h1>', $markdown);
        $this->assertStringContainsString('<p>Paragraph content</p>', $markdown);
    }

    /**
     * Test clean filename functionality
     */
    public function test_get_clean_filename() {
        $exporter = new \block_managepages\exporter();
        
        // Test various filename scenarios
        $this->assertEquals('normal_filename', $exporter->get_clean_filename('normal filename'));
        $this->assertEquals('Special_Characters', $exporter->get_clean_filename('Special @#$% Characters!'));
        $this->assertEquals('numbers_123_test', $exporter->get_clean_filename('numbers 123 test'));
        $this->assertEquals('', $exporter->get_clean_filename('   '));
        $this->assertEquals('hyphen-test', $exporter->get_clean_filename('hyphen-test'));
        $this->assertEquals('underscore_test', $exporter->get_clean_filename('underscore_test'));
    }

    /**
     * Test structured markdown generation
     */
    public function test_get_structured_markdown() {
        // Create test course with sections
        $course = $this->getDataGenerator()->create_course();
        
        // Create pages in different sections
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
        
        // Create page objects
        $pages = [
            (object)[
                'id' => $page1->id,
                'name' => 'Page 1',
                'course' => $course->id,
                'content' => '<p>Content 1</p>'
            ],
            (object)[
                'id' => $page2->id,
                'name' => 'Page 2',
                'course' => $course->id,
                'content' => '<p>Content 2</p>'
            ]
        ];
        
        $exporter = new \block_managepages\exporter();
        $structured_markdown = $exporter->get_structured_markdown($pages, $course->id);
        
        // Test that structured markdown contains expected elements
        $this->assertStringContainsString('## Page 1', $structured_markdown);
        $this->assertStringContainsString('## Page 2', $structured_markdown);
        $this->assertStringContainsString('<p>Content 1</p>', $structured_markdown);
        $this->assertStringContainsString('<p>Content 2</p>', $structured_markdown);
    }

    /**
     * Test ZIP creation functionality
     */
    public function test_create_zip() {
        $exporter = new \block_managepages\exporter();
        
        // Create test markdown files
        $markdown_files = [
            'page1.md' => "# Page 1\n\nContent of page 1",
            'section1/page2.md' => "# Page 2\n\nContent of page 2",
            'section2/page3.md' => "# Page 3\n\nContent of page 3"
        ];
        
        $zip_path = $exporter->create_zip($markdown_files);
        
        // Test that ZIP was created
        $this->assertNotFalse($zip_path);
        $this->assertTrue(file_exists($zip_path));
        
        // Test ZIP contents
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zip_path));
        
        $this->assertEquals(3, $zip->numFiles);
        
        // Check if specific files exist in ZIP
        $this->assertNotFalse($zip->locateName('page1.md'));
        $this->assertNotFalse($zip->locateName('section1/page2.md'));
        $this->assertNotFalse($zip->locateName('section2/page3.md'));
        
        $zip->close();
        
        // Clean up
        unlink($zip_path);
    }

    /**
     * Test page section path retrieval
     */
    public function test_get_page_section_path() {
        // Create test course
        $course = $this->getDataGenerator()->create_course();
        
        // Create page
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Test Page',
            'content' => '<p>Test content</p>'
        ]);
        
        // Create page object
        $page_obj = (object)[
            'id' => $page->id,
            'course' => $course->id
        ];
        
        $exporter = new \block_managepages\exporter();
        $section_path = $exporter->get_page_section_path($page_obj);
        
        // Should return a string (may be empty for section 0)
        $this->assertIsString($section_path);
    }    /**
     * Test handling of invalid page ID
     */
    public function test_get_page_content_invalid_id() {
        $exporter = new \block_managepages\exporter();
        
        $this->expectException(\dml_missing_record_exception::class);
        $exporter->get_page_content(99999); // Non-existent page ID
    }
}
