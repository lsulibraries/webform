<?php

namespace Drupal\webform\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for converting uploaded HTML files to text.
 */
class WebformAdminHtmlToTextForm extends ConfirmFormBase {

  /**
   * Default number of files to be converted during batch processing.
   *
   * @var int
   */
  protected $batchLimit = 100;

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * The entity query object.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQueryFactory;

  /**
   * Constructs a WebformAdminHtmlToTextForm object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   The file storage service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The query object that can query the given entity type.
   */
  public function __construct(EntityStorageInterface $file_storage, QueryFactory $entity_query) {
    $this->fileStorage = $file_storage;
    $this->entityQueryFactory = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('file'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_html_to_text';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to convert @total files(s) from HTML to text?', ['@total' => $this->getTotal()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '<p>' . $this->t('All *.html and *.hml file extensions will be suffixed with *.txt, this will force all HTML files to be displayed as plain text.') . '</p>' .
      '<p>' . $this->t('This action cannot be undone.') . '</p>';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Convert HTML files to text');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.status');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->getCancelUrl());
    $this->batchSet();
  }

  /**
   * Message to displayed after HTML files have been converted.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Message to be displayed after HTML files have been converted.
   */
  public function getFinishedMessage() {
    return $this->t('HTML files conversion to text completed.');
  }

  /**
   * Batch API; Initialize batch operations.
   */
  public function batchSet() {
    $parameters = [];
    $batch = [
      'title' => $this->t('Convert HTML files to text'),
      'init_message' => $this->t('Convert HTML files to text'),
      'error_message' => $this->t('The HTML files could not be converted to text because an error occurred.'),
      'operations' => [
        [[$this, 'batchProcess'], $parameters],
      ],
      'finished' => [$this, 'batchFinish'],
    ];

    batch_set($batch);
  }

  /**
   * Batch API callback; Convert HTML files to text.
   *
   * @param mixed|array $context
   *   The batch current context.
   */
  public function batchProcess(&$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $this->getTotal();
    }

    $entity_ids = $this->getQuery()->range(0, $this->batchLimit)->execute();
    if ($entity_ids) {
      /** @var \Drupal\file\FileInterface[] $files */
      $files = $this->fileStorage->loadMultiple($entity_ids);
      foreach ($files as $file) {
        $file->setMimeType('text/plain');
        if (file_move($file, $file->getFileUri() .'.txt')) {
          $file->setFilename($file->getFilename() .'.txt');
          $file->setFileUri($file->getFileUri() .'.txt');
          $file->setMimeType('text/plain');
          $file->save();
        }
      }
    }

    // Track progress.
    $context['sandbox']['progress'] += count($entity_ids);

    // Display progress.
    $context['message'] = $this->t('Converting @count of @total files...', ['@count' => $context['sandbox']['progress'], '@total' => $context['sandbox']['max']]);

    // Track finished.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Batch API callback; Completed conversion.
   *
   * @param bool $success
   *   TRUE if batch successfully completed.
   * @param array $results
   *   Batch results.
   * @param array $operations
   *   An array of function calls (not used in this function).
   */
  public function batchFinish($success = FALSE, array $results, array $operations) {
    if (!$success) {
      drupal_set_message($this->t('Finished with an error.'));
    }
    else {
      drupal_set_message($this->getFinishedMessage());
    }
  }

  /**
   * Get an entity query containing all HTML files.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query containing all webform HTML files.
   */
  protected function getQuery() {
    $query = $this->entityQueryFactory->get('file');
    $query ->condition('uri', '%://webform/%', 'LIKE')
      ->condition(
        $query->orConditionGroup()
          ->condition('filename', '%.htm', 'LIKE')
          ->condition('filename', '%.html', 'LIKE')
      );
    $query->sort('fid');
    return $query;
  }

  /**
   * Get the total number of HTML files.
   *
   * @return int
   *   The total number of HTML files.
   */
  protected function getTotal() {
    return $this->getQuery()->count()->execute();
  }

}
