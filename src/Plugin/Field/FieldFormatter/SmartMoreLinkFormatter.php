<?php

namespace Drupal\smart_more_link\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;
use Drupal\text\Plugin\Field\FieldFormatter\TextSummaryOrTrimmedFormatter;

/**
 * Plugin implementation of the 'Random_default' formatter.
 *
 * @FieldFormatter(
 *   id = "smart_more_link",
 *   label = @Translation("Smart more link"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class SmartMoreLinkFormatter extends FormatterBase {

  /**
   * @var TextSummaryOrTrimmedFormatter
   */
  protected $summaryFormatter;
  /**
   * @var TextSummaryOrTrimmedFormatter
   */
  protected $defaultFormatter;

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    /**
     * Ideally would build these using some factory method so they could be mocked.
     */
    $this->summaryFormatter = new TextSummaryOrTrimmedFormatter(
      'text_summary_or_trimmed',
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );
    $this->defaultFormatter = new TextDefaultFormatter(
      'text_default',
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );
  }


  public function settingsForm(array $form, FormStateInterface $form_state) {
    return $this->summaryFormatter->settingsForm($form, $form_state);
  }

  public function settingsSummary() {
    return $this->summaryFormatter->settingsSummary();
  }

  /**
   * @inheritdoc
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = $this->summaryFormatter->viewElements($items, $langcode);
    $defaults = $this->defaultFormatter->viewElements($items, $langcode);
    $elementsClone = $elements;
    $elementsMarkup = render($elementsClone);
    $defaultsMarkup = render($defaults);
    $readMore = (string)$elementsMarkup !== (string)$defaultsMarkup;
    if ($readMore) {
      $entity = $items->getEntity();
      /*
       * Copied from NodeViewBuilder::buildLinks()
       * - could possibly invoke it directly instead
       * but that would not be good either
       */
      $node_title_stripped = strip_tags($entity->label());
      $links['body-readmore'] = [
        'title' => t('Read more<span class="visually-hidden"> about @title</span>', [
          '@title' => $node_title_stripped,
        ]),
        'url' => $entity->urlInfo(),
        'language' => $entity->language(),
        'attributes' => [
          'rel' => 'tag',
          'title' => $node_title_stripped,
        ],
      ];
      $elements[count($elements) -1]['links'] = [
        '#theme' => 'links__node__node',
        '#links' => $links,
        '#attributes' => ['class' => ['links', 'inline']],
      ];

    }
    return $elements;
  }
}