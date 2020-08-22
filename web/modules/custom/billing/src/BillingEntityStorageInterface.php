<?php

namespace Drupal\billing;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface BillingEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Billing revision IDs for a specific Billing.
   *
   * @param \Drupal\billing\Entity\BillingEntityInterface $entity
   *   The Billing entity.
   *
   * @return int[]
   *   Billing revision IDs (in ascending order).
   */
  public function revisionIds(BillingEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Billing author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Billing revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\billing\Entity\BillingEntityInterface $entity
   *   The Billing entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(BillingEntityInterface $entity);

  /**
   * Unsets the language for all Billing with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
