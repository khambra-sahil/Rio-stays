<?php

namespace Drupal\billing\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Billing entity.
 *
 * @ingroup billing
 *
 * @ContentEntityType(
 *   id = "billing_entity",
 *   label = @Translation("Billing"),
 *   bundle_label = @Translation("Billing type"),
 *   handlers = {
 *     "storage" = "Drupal\billing\BillingEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\billing\BillingEntityListBuilder",
 *     "views_data" = "Drupal\billing\Entity\BillingEntityViewsData",
 *     "translation" = "Drupal\billing\BillingEntityTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\billing\Form\BillingEntityForm",
 *       "add" = "Drupal\billing\Form\BillingEntityForm",
 *       "edit" = "Drupal\billing\Form\BillingEntityForm",
 *       "delete" = "Drupal\billing\Form\BillingEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\billing\BillingEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\billing\BillingEntityAccessControlHandler",
 *   },
 *   base_table = "billing_entity",
 *   data_table = "billing_entity_field_data",
 *   revision_table = "billing_entity_revision",
 *   revision_data_table = "billing_entity_field_revision",
 *   translatable = TRUE,
 *   permission_granularity = "bundle",
 *   admin_permission = "administer billing entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/billing_entity/{billing_entity}",
 *     "add-page" = "/admin/structure/billing_entity/add",
 *     "add-form" = "/admin/structure/billing_entity/add/{billing_entity_type}",
 *     "edit-form" = "/admin/structure/billing_entity/{billing_entity}/edit",
 *     "delete-form" = "/admin/structure/billing_entity/{billing_entity}/delete",
 *     "version-history" = "/admin/structure/billing_entity/{billing_entity}/revisions",
 *     "revision" = "/admin/structure/billing_entity/{billing_entity}/revisions/{billing_entity_revision}/view",
 *     "revision_revert" = "/admin/structure/billing_entity/{billing_entity}/revisions/{billing_entity_revision}/revert",
 *     "revision_delete" = "/admin/structure/billing_entity/{billing_entity}/revisions/{billing_entity_revision}/delete",
 *     "translation_revert" = "/admin/structure/billing_entity/{billing_entity}/revisions/{billing_entity_revision}/revert/{langcode}",
 *     "collection" = "/admin/structure/billing_entity",
 *   },
 *   bundle_entity_type = "billing_entity_type",
 *   field_ui_base_route = "entity.billing_entity_type.edit_form"
 * )
 */
class BillingEntity extends EditorialContentEntityBase implements BillingEntityInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly,
    // make the billing_entity owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['particular'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Particular'))
      ->setDescription(t('The name of the Billing entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['rate'] = BaseFieldDefinition::create('float')
     ->setLabel(t('Rate'))
     ->setDescription(t('Rate of the item'))
     ->setRevisionable(TRUE)
     ->setTranslatable(TRUE)
     ->setDisplayOptions('form', array(
       'type' => 'string_textfield',
       'settings' => array(
         'display_label' => TRUE,
       ),
     ))
    ->setDisplayOptions('view', array(
       'label' => 'hidden',
       'type' => 'string',
     ))
     ->setDisplayConfigurable('form', TRUE)
     ->setRequired(TRUE);

     $fields['amount'] = BaseFieldDefinition::create('float')
     ->setLabel(t('Amount'))
     ->setDescription(t('Total cost of the item'))
     ->setRevisionable(TRUE)
     ->setTranslatable(TRUE)
     ->setDisplayOptions('form', array(
       'type' => 'string_textfield',
       'settings' => array(
         'display_label' => TRUE,
       ),
     ))
    ->setDisplayOptions('view', array(
       'label' => 'hidden',
       'type' => 'string',
     ))
     ->setDisplayConfigurable('form', TRUE)
     ->setRequired(TRUE);

    $fields['hotel_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Hotel ID'))
      ->setDescription(t('ID of the Hotel'))
      ->setReadOnly(TRUE);

    $fields['room_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Room Number'))
      ->setDescription(t('Room Number'))
      ->setReadOnly(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Billing is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['updated'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Updated'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
