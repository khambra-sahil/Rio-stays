<?php

namespace Drupal\billing\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Billing type entity.
 *
 * @ConfigEntityType(
 *   id = "billing_entity_type",
 *   label = @Translation("Billing type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\billing\BillingEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\billing\Form\BillingEntityTypeForm",
 *       "edit" = "Drupal\billing\Form\BillingEntityTypeForm",
 *       "delete" = "Drupal\billing\Form\BillingEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\billing\BillingEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "billing_entity_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "billing_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/[A[B/admin/structure/billing_entity_type/{billing_entity_type}",
 *     "add-form" = "/[A[B/admin/structure/billing_entity_type/add",
 *     "edit-form" = "/[A[B/admin/structure/billing_entity_type/{billing_entity_type}/edit",
 *     "delete-form" = "/[A[B/admin/structure/billing_entity_type/{billing_entity_type}/delete",
 *     "collection" = "/[A[B/admin/structure/billing_entity_type"
 *   }
 * )
 */
class BillingEntityType extends ConfigEntityBundleBase implements BillingEntityTypeInterface {

  /**
   * The Billing type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Billing type label.
   *
   * @var string
   */
  protected $label;

}
