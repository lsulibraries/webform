<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformActions as WebformActionsElement;
use Drupal\webform\WebformInterface;

/**
 * Provides a 'webform_actions' element.
 *
 * @WebformElement(
 *   id = "webform_actions",
 *   label = @Translation("Submit button(s)"),
 *   description = @Translation("Provides an element that contains a Webform's submit, draft, wizard, and/or preview buttons."),
 *   category = @Translation("Actions"),
 * )
 */
class WebformActions extends ContainerBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      // Title.
      'title' => '',
      // Attributes.
      'attributes' => [],
      // Conditional logic.
      'states' => [],
    ];
    foreach (WebformActionsElement::$buttons as $button) {
      $properties[$button . '_hide'] = FALSE;
      $properties[$button . '__label'] = '';
      $properties[$button . '__attributes'] = [];
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isRoot() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function build($format, array &$element, $value, array $options = []) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Containers should never have values and therefore should never have
    // a test value.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getWebform();

    $form['actions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Buttons'),
    ];
    $draft_enabled = ($webform->getSetting('draft') != WebformInterface::DRAFT_ENABLED_NONE);
    $wizard_enabled = $webform->isWizard();
    $preview_enabled = ($webform->getSetting('preview') != DRUPAL_DISABLED);

    $buttons = [
      'submit' => [
        'title' => $this->t('Submit'),
        'label' => $this->t('submit'),
        'access' => TRUE,
      ],
      'draft' => [
        'title' => $this->t('Draft'),
        'label' => $this->t('draft'),
        'access' => $draft_enabled,
      ],
      'wizard_prev' => [
        'title' => $this->t('Wizard previous'),
        'label' => $this->t('wizard previous'),
        'description' => $this->t('This is used for the previous page button within a wizard.'),
        'access' => $wizard_enabled,

      ],
      'wizard_next' => [
        'title' => $this->t('Wizard next'),
        'label' => $this->t('wizard next'),
        'description' => $this->t('This is used for the next page button within a wizard.'),
        'access' => $wizard_enabled,
      ],
      'preview_prev' => [
        'title' => $this->t('Preview previous'),
        'label' => $this->t('preview previous'),
        'description' => $this->t('The text for the button that will proceed to the preview page.'),
        'access' => $preview_enabled,
      ],
      'preview_next' => [
        'title' => $this->t('Preview next'),
        'label' => $this->t('preview next'),
        'description' => $this->t('The text for the button to go backwards from the preview page.'),
        'access' => $preview_enabled,
      ],
    ];

    foreach ($buttons as $name => $button) {
      $t_args = [
        '@title' => $button['title'],
        '@label' => $button['label'],
      ];
      $states = [
        'visible' => [':input[name="properties[' . $name . '_hide]"]' => ['checked' => FALSE]],
      ];

      $form[$name . '_settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('@title button', $t_args),
        '#access' => $button['access'],
      ];
      if (!empty($button['description'])) {
        $form[$name . '_settings']['description'] = [
          '#markup' => $button['description'],
          '#access' => TRUE,
        ];
      }
      $form[$name . '_settings'][$name . '_hide'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide @label button', $t_args),
        '#return_value' => TRUE,
        '#access' => $webform->getNumberOfActions() > 1 ? TRUE : FALSE,
      ];
      $form[$name . '_settings'][$name . '__label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('@title button label', $t_args),
        '#description' => $this->t('Defaults to: %value', ['%value' => $this->configFactory->get('webform.settings')->get('settings.default_' . $name . '_button_label')]),
        '#size' => 20,
        '#states' => $states,
      ];
      $form[$name . '_settings'][$name . '__attributes'] = [
        '#type' => 'webform_element_attributes',
        '#title' => $this->t('@title button', $t_args),
        '#classes' => $this->configFactory->get('webform.settings')->get('settings.button_classes'),
        '#states' => $states,
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getWebform();

    if (!$webform->hasActions()) {
      $form['element']['title']['#default_value'] = $this->t('Actions');
      $this->key = 'acccc';
    }
    return $form;
  }


}
