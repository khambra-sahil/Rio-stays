<?php

namespace Drupal\billing;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\billing\Entity\BillingEntityInterface;

/**
 * Defines the storage handler class for Billing entities.
 *
 * This extends the base storage class, adding required special handling for
 * Billing entities.
 *
 * @ingroup billing
 */
class BillingEntityStorage extends SqlContentEntityStorage implements BillingEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(BillingEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {billing_entity_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {billing_entity_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(BillingEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {billing_entity_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('billing_entity_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
