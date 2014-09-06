<?php

/**
 * @file
 * Contains \Drupal\file_entity\Form\FileTypeDeleteForm.
 */

namespace Drupal\file_entity\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete a file type.
 */
class FileTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t(
      'Are you sure you want to delete the file type %name?',
      array('%name' => $this->entity->label())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message(t(
      'The file type %label has been deleted.',
      array('%label' => $this->entity->label())
    ));
    $form_state->setRedirect('entity.file_type.canonical');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->urlInfo();
  }
}
