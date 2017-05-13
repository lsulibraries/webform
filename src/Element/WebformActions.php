<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Container;

/**
 * Provides a wrapper element to group one or more Webform buttons in a form.
 *
 * @RenderElement("webform_actions")
 *
 * @see \Drupal\Core\Render\Element\Actions
 */
class WebformActions extends Container {

  public static $buttons = [
    'submit',
    'draft',
    'wizard_prev',
    'wizard_next',
    'preview_prev',
    'preview_next',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'processWebformActions'],
        [$class, 'processContainer'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes a form actions container element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   form actions container.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processWebformActions(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#attributes']['class'][] = 'form-actions';
    $element['#attributes']['class'][] = 'webform-actions';

    // Copy the form's actions to this element.
    $element += $complete_form['actions'];

    // Track if buttons are visible.
    $has_visible_button = FALSE;
    foreach (static::$buttons as $button) {
      // Make sure the button exists.
      if (!isset($element[$button])) {
        continue;
      }

      // Hide buttons using #access.
      if (!empty($element['#' . $button .'_hide'])) {
        $element[$button]['#access'] = FALSE;
      }

      // Apply custom label.
      if (!empty($element['#' . $button .'__label']) && empty($element[$button]['#webform_actions_button_custom'])) {
        $element[$button]['#value'] = $element['#' . $button .'__label'];
      }

      // Apply attributes (class, style, properties).
      if (!empty($element['#' . $button .'__attributes'])) {
        foreach ($element['#' . $button .'__attributes'] as $name => $value) {
          if ($name == 'class') {
            // Merge class names.
            $element[$button]['#attributes']['class'] = array_merge($element[$button]['#attributes']['class'], $value);
          }
          else {
            $element[$button]['#attributes'][$name] = $value;
          }
        };
      }

      if (!isset($element[$button]['#access']) || $element[$button]['#access'] === TRUE) {
        $has_visible_button = TRUE;
      }
    }

    // Hide actions element if no buttons are visible (ie #access = FALSE).
    if (!$has_visible_button) {
      $element['#access'] = FALSE;
    }

    // Hide form actions.
    $complete_form['actions']['#access'] = FALSE;

    return $element;
  }

}
