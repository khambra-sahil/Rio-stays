<?php

namespace Drupal\guest_app_custom\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\paragraphs\Entity\Paragraph;
//use Drupal\Core\Form\FormStateInterface;
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

    //kint($current_form_id);
    //exit;

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

    if($current_form_id == 'node_upcoming_check_ins_form'){

      $form['field_upc_user_room_type']['widget']['#options'] = get_room_type_options();
      // check on booking status
      $value = $form['field_booking_status']['widget']['#default_value'][0];
      if($value == '1' || $value == NULL){
      unset($form['field_booking_status']['widget']['#options']['3']);
      }
      if($value == '2'){
      unset($form['field_booking_status']['widget']['#options']['1']);
      }
      if($value == '3'){
      $form['field_booking_status']['widget']['#attributes']['disabled'] = 'disabled';
      }
      // validate upcoming check-ins insert
      $form['#validate'][] = 'upcoming_check_ins_validate';
    }

    if($current_form_id == 'node_upcoming_check_ins_edit_form'){
      $form['field_upc_user_room_type']['widget']['#options'] = get_room_type_options();

      // check on booking status
      $value = $form['field_booking_status']['widget']['#default_value'][0];
      if($value == '1' || $value == NULL){
      unset($form['field_booking_status']['widget']['#options']['3']);
      }
      if($value == '2'){
      unset($form['field_booking_status']['widget']['#options']['1']);
      }
      if($value == '3'){
      $form['field_booking_status']['widget']['#attributes']['disabled'] = 'disabled';
      }

      // validate upcoming check-ins edit
      //$form['#validate'][] = 'upcoming_check_edit_validate';
    }

    if($current_form_id == 'node_hotel_inventory_form' || $current_form_id == 'node_hotel_inventory_edit_form'){
      $form['#validate'][] = 'hotel_inventory_validate';
    }

    $event->setForm($form);
  }
}
