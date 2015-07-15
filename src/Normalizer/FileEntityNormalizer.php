<?php
/**
 * @file
 * Contains \Drupal\file_entity\Normalizer\FileEntityNormalizer.
 */

namespace Drupal\file_entity\Normalizer;

use Drupal\Component\Utility\String;
use Drupal\hal\Normalizer\ContentEntityNormalizer;
use Gliph\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Normalizer for File entity.
 */
class FileEntityNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\file\FileInterface';

  /**
   * {@inheritdoc}
   */
  protected function getEntityUri($entity) {
    // The URI should refer to the entity, not only directly to the file.
    global $base_url;
    return $base_url . $entity->urlInfo()->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $data = parent::normalize($entity, $format, $context);
    if (!isset($context['included_fields']) || in_array('data', $context['included_fields'])) {
      // Save base64-encoded file contents to the "data" property.
      $file_data = base64_encode(file_get_contents($entity->getFileUri()));
      // @todo these lined commented to stop entire file add to api response
      // $data += array(
      //   'data' => array(array('value' => $file_data)),
      // );
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    // Avoid 'data' being treated as a field.
    $file_data = $data['data'][0]['value'];
    unset($data['data']);
    // If the file is not base64 encoded do something else
    if ( strpos($file_data, 'http') === 0 ) {
      $file_contents = file_get_contents($file_data);
    } else {
      $file_contents = base64_decode($file_data);
    }
    // Decode and save to file.
    $entity = parent::denormalize($data, $class, $format, $context);
    $dirname = drupal_dirname($entity->getFileUri());
    file_prepare_directory($dirname, FILE_CREATE_DIRECTORY);
    if ($uri = file_unmanaged_save_data($file_contents, $entity->getFileUri())) {
      $entity->setFileUri($uri);
    }
    else {
      throw new RuntimeException(String::format('Failed to write @filename.', array('@filename' => $entity->getFilename())));
    }
    return $entity;
  }
}
