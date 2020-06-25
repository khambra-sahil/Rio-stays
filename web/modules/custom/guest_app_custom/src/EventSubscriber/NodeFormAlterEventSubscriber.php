<?php

namespace Drupal\guest_app_custom\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;

/**
 * The class is used to implement event for form alter.
 *
 * @package Drupal\etisc_form_alter\EventSubscriber
 */
class NodeFormAlterEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::FORM_ALTER => 'nodeFormAlter',
    ];
  }

  /**
   * Implements hook_form_alter().
   *
   * @param array $event
   *   Event array.
   */
  public function nodeFormAlter($event)
  {
    $current_form_id = $event->getFormId();

    $form = $event->getForm();

    //request form alter
    if($current_form_id == 'node_requests_edit_form'){
      //get current selected value
      $value = $form['field_order_status']['widget']['#default_value'][0];
      if($value == 'pending' || $value == NULL){
        unset($form['field_order_status']['widget']['#options']['revert_inventory']);
      }
      if($value == 'confirm'){
        unset($form['field_order_status']['widget']['#options']['pending']);
      }
      if($value == 'revert_inventory'){
        $form['field_order_status']['widget']['#attributes']['disabled'] = 'disabled';
      }
    }

    $event->setForm($form);
  }
}
