<?php

namespace Drupal\billing\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Billing entities.
 *
 * @ingroup billing
 */
interface BillingEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Billing name.
   *
   * @return string
   *   Name of the Billing.
   */
  public function getName();

  /**
   * Sets the Billing name.
   *
   * @param string $name
   *   The Billing name.
   *
   * @return \Drupal\billing\Entity\BillingEntityInterface
   *   The called Billing entity.
   */
  public function setName($name);

  /**
   * Gets the Billing creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Billing.
   */
  public function getCreatedTime();

  /**
   * Sets the Billing creation timestamp.
   *
   * @param int $timestamp
   *   The Billing creation timestamp.
   *
   * @return \Drupal\billing\Entity\BillingEntityInterface
   *   The called Billing entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Billing revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Billing revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\billing\Entity\BillingEntityInterface
   *   The called Billing entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Billing revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Billing revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\billing\Entity\BillingEntityInterface
   *   The called Billing entity.
   */
  public function setRevisionUserId($uid);

}
