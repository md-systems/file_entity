<?php

/**
 * @file
 * Contains \Drupal\file_entity\Plugin\Action\FileSetTemporary.
 */

namespace Drupal\file_entity\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\file_entity\Entity\FileEntity;

/**
 * Sets the file status to temporary.
 *
 * @Action(
 *   id = "file_temporary_action",
 *   label = @Translation("Set file status to temporary"),
 *   type = "file"
 * )
 */
class FileSetTemporary extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var FileEntity $entity */
    $entity->setTemporary();
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    foreach ( $entities as $entity ) {
      $entity->setTemporary();
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return true;
  }

}
