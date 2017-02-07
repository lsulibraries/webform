<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Test for webform element managed file HTML upload blocking.
 *
 * @group Webform
 */
class WebformElementManagedFileHtmlTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_managed_file_html'];

  /**
   * Test HTML file upload blocking.
   */
  public function testHtmlFileUploadBlocking() {
    $webform = Webform::load('test_element_managed_file_html');
    $files = $this->drupalGetTestFiles('html');
    $admin_user = $this->createUser(['administer site configuration', 'administer webform']);
    $this->drupalLogin($admin_user );

    // Attempt to upload an HTML file.
    $edit = [
      'files[managed_file_html]' => \Drupal::service('file_system')->realpath($files[1]->uri),
    ];
    $this->postSubmission($webform, $edit);

    // Check that HTML file upload is blocked.
    $this->assertFalse($this->getLastFileId());

    // Remove XSS block.
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('file.xss_block', FALSE)
      ->save();

    // Upload HTML files
    $this->postSubmission($webform, $edit);

    // Check that HTML file upload is allowed.
    $this->assertTrue($this->getLastFileId());

    // Get the 'Status report'.
    $this->drupalGet('admin/reports/status');

    // Check †hat 'Webform files: HTML file uploads' warning is displayed.
    $this->assertText('HTML files that may contain Cross-Site Scripting (XSS) have been uploaded. You should convert 1 existing file(s) from HTML to plain text.');

    // Check †hat 'Webform files: XSS block' warning is displayed.
    $this->assertText('Blocking users from uploading HTML files, which may contain Cross-Site Scripting (XSS) is not set.');

    // Execute 'Convert HTML files to text' batch process.
    $this->clickLink('HTML to plain text');
    $this->drupalPostForm(NULL, [], t('Convert HTML files to text'));

    // Check †hat 'Webform files: HTML file uploads' is no longer displayed.
    $this->assertUrl('admin/reports/status');
    $this->assertNoText('HTML files that may contain Cross-Site Scripting (XSS) have been uploaded. You should convert 1 existing file(s) from HTML to plain text.');
  }

  /**
   * Retrieves the fid of the last inserted file.
   */
  protected function getLastFileId() {
    return (int) db_query('SELECT MAX(fid) FROM {file_managed}')->fetchField();
  }

}
