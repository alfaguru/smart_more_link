<?php

namespace Drupal\Tests\smart_more_link\Functional;


use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * @group smart_more_link
 */
class SmartMoreLinkTest extends BrowserTestBase {

  protected static $modules = ['node', 'field_ui', 'filter', 'views', 'smart_more_link'];


  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $data = [
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 2,
    ];
    $ff = $this->container->get('entity_type.manager')->getStorage('filter_format')
      ->create($data);
    $ff->save();

    // Create an article content type that we will use for testing.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();

    node_add_body_field($type);
    $this->container->get('router.builder')->rebuild();
    $this->configureField();
  }

  /**
   * Check we can configure the article type to use our formatter
   * @throws
   */
  public function testConfigureContentType() {
    $this->assertSession()->pageTextContains('Your settings have been saved');
  }

  public function testCreateArticleWithShortBody() {

    $node = Node::create([
      'type'        => 'article',
      'title'       => 'Test 1',
      'body' => [
        'summary' => '',
        'value' => '<p>The body of my node.</p>',
        'format' => 'full_html',
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    $this->assertSession()->pageTextContains('The body of my node');
    $this->drupalGet('/node' );
    $this->assertSession()->pageTextNotContains('Read more');
  }

  public function testCreateArticleWithLongBody() {
    $body = $this->paragraphs(12);
    $node = Node::create([
      'type'        => 'article',
      'title'       => 'Test 2',
      'body' => [
        'summary' => '',
        'value' => $this->htmlParagraphs($body),
        'format' => 'full_html',
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    $this->assertParagraphs($body);
    $this->drupalGet('/node' );
    $this->assertParagraphs([$body[0]]);
    $this->assertSession()->linkExists('Read more');

  }

  public function testCreateArticleWithSummary() {
    $body = $this->paragraphs();
    $summary = $this->paragraphs(1);
    $node = Node::create([
      'type'        => 'article',
      'title'       => 'Test 2',
      'body' => [
        'summary' => $this->htmlParagraphs($summary),
        'value' => $this->htmlParagraphs($body),
        'format' => 'full_html',
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    $this->assertParagraphs($body);
    $this->drupalGet('/node' );
    $this->assertParagraphs($summary);
    $this->assertSession()->linkExists('Read more');
  }

  protected function configureField() {
    $account = $this->drupalCreateUser([
      'administer nodes',
      'administer content types',
      'administer node fields',
      'administer node display',
      'bypass node access',
      'use text format full_html',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/structure/types/manage/article/display/teaser');
    $this->getSession()->getPage()->selectFieldOption('edit-fields-links-region', 'hidden');
    $this->getSession()->getPage()->selectFieldOption('edit-fields-body-type', 'smart_more_link');
    $this->getSession()->getPage()->pressButton('Save');

  }

  protected function paragraphs($count = 12) {
    $result = [];
    for ($i = 0; $i <  $count; $i++) {
      $result[] = $this->getRandomGenerator()->sentences(25, TRUE);
    }
    return $result;
  }

  protected function assertParagraphs(array $paragraphs) {
    foreach ($paragraphs as $paragraph) {
      $this->assertSession()->pageTextContains($paragraph);
    }
  }

  protected function htmlParagraphs(array $paragraphs) {
    return '<p>' . join('</p><p>', $paragraphs) . '</p>';
  }


}