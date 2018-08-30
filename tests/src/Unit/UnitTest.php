<?php

namespace Drupal\Tests\smart_more_link\Unit;


use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RendererInterface;
use Drupal\smart_more_link\Plugin\Field\FieldFormatter\SmartMoreLinkFormatter;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UnitTest extends UnitTestCase {

  /**
   * Test that the formatter delegates its settings as expected.
   */
  public function testDelegationOfSettings() {
    $field_definition = $this->getMockBuilder(FieldDefinitionInterface::class)->getMock();
    $base_formatter = $this->getMockBuilder(FormatterBase::class)->disableOriginalConstructor()->getMock();

    $random_string1 = $this->getRandomGenerator()->string(20);
    $random_string2 = $this->getRandomGenerator()->string(20);
    $base_formatter->method('settingsForm')->willReturn([
      '#type' => 'markup',
      '#markup' => $random_string1,
    ]);
    $base_formatter->method('settingsSummary')->willReturn($random_string2);
    $default_formatter = $this->getMockBuilder(FormatterBase::class)->disableOriginalConstructor()->getMock();
    $formatter_manager = $this->getMockBuilder(PluginManagerInterface::class)->getMock();
    $formatter_manager->method('createInstance')
      ->willReturnCallback(function ($plugin_id) use ($default_formatter, $base_formatter)
      {
        switch($plugin_id) {
          case 'text_summary_or_trimmed':
            return $base_formatter;
          default:
            return $default_formatter;
        }
      });
    $renderer = $this->getMockBuilder(RendererInterface::class)->getMock();

    $formatter = new SmartMoreLinkFormatter(
      'smart_more_link',
      [],
      $field_definition,
      [],
      'Field label',
      'view_mode',
      [],
      $formatter_manager,
      $renderer
    );
    $form_state = $this->getMockBuilder(FormState::class)->getMock();
    $settings_form = $formatter->settingsForm([], $form_state);
    $this->assertEquals($settings_form['#markup'], $random_string1, 'delegates form to base formatter');
    $settings_summary = $formatter->settingsSummary();
    $this->assertEquals($settings_summary, $random_string2, 'delegates summary to base formatter');
  }

  /**
   * Test no link is generated when teaser and full views match.
   */
  public function testWhenTeaserMatchesFull() {
    $field_definition = $this->getMockBuilder(FieldDefinitionInterface::class)->getMock();
    $base_formatter = $this->getMockBuilder(FormatterBase::class)->disableOriginalConstructor()->getMock();

    $random_string1 = $this->getRandomGenerator()->string(20);
    $default_formatter = $this->getMockBuilder(FormatterBase::class)->disableOriginalConstructor()->getMock();
    $formatter_manager = $this->getMockBuilder(PluginManagerInterface::class)->getMock();
    $formatter_manager->method('createInstance')
      ->willReturnCallback(function ($plugin_id) use ($default_formatter, $base_formatter)
      {
        switch($plugin_id) {
          case 'text_summary_or_trimmed':
            return $base_formatter;
          default:
            return $default_formatter;
        }
      });
    $renderer = $this->getMockBuilder(RendererInterface::class)->getMock();
    // rendered view is constant
    $renderer->method('render')
      ->willReturn($random_string1);
    $formatter = new SmartMoreLinkFormatter(
      'smart_more_link',
      [],
      $field_definition,
      [],
      'Field label',
      'view_mode',
      [],
      $formatter_manager,
      $renderer = $this->getMockBuilder(RendererInterface::class)->getMock()
  );
    $items = $this->getMockBuilder(FieldItemListInterface::class)->getMock();
    $result = $formatter->viewElements($items, 'und');
    // @todo be more thorough about this
    $this->assertEmpty($result[0]['link'], 'no link in output');
  }
}