<?php

namespace Drupal\guest_app_custom\EventSubscriber;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;


/**
 * The class is used to implement event for form alter.
 *
 * @package Drupal\trigyn_wiki_form_alter\EventSubscriber
 */
class UserCreateAlterEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::ENTITY_PRE_SAVE => 'userCreateAlter',
    ];
  }

  /**
   * Implements hook_entity_create().
   *
   * @param array $event
   *   Event array.
   */
  public function userCreateAlter($event) {
    $entity = $event->getEntity();
    if($entity->getEntityTypeId() == 'user'){
      $uid = $entity->id();
      $old_checkout_date_value = 0;
      $current_date = \Drupal::time()->getCurrentTime();
      if(!empty($entity->get('field_check_in_checkout_date'))) {
        $new_check_in_out_date = $entity->get('field_check_in_checkout_date')->getValue();
        $new_checkin_date_value = $new_check_in_out_date[0]['value'];
        $check_in_date_read_able = date('d-M-Y',$new_checkin_date_value );
        $new_checkout_date_value = $new_check_in_out_date[0]['end_value'];
        $check_out_date_read_able = date('d-M-Y',$new_checkout_date_value );
      }
      if($entity->original != NULL) {
        $old_checkout_date = $entity->original->get('field_check_in_checkout_date')->getValue();
        $old_checkout_date_value = $old_checkout_date[0]['end_value'];
      }
      if(!empty($entity->get('field_hotel')->getValue())) {
        $hotel_id = $entity->get('field_hotel')->getValue();
        $hotel_load = \Drupal\node\Entity\Node::load($hotel_id[0]['target_id']);
        $hotel_title = $hotel_load->getTitle();
      }
      if(!empty($entity->get('field_from_city'))){
        $from_city = $entity->get('field_from_city')->getValue();
      }
      if(!empty($entity->get('field_from_state'))){
        $from_state = $entity->get('field_from_state')->getValue();
      }
      if(!empty($entity->get('field_plan_type'))){
        $plan_type = $entity->get('field_plan_type')->getValue();
      }
      if(!empty($entity->get('field_room_type'))){
        $room_type = $entity->get('field_room_type')->getValue();
      }
      $first_name = $entity->get('field_first_name')->getValue();
      $last_name = $entity->get('field_last_name')->getValue();
      $message = $first_name[0]['value'].' '.$last_name[0]['value']. ' stay in '. $hotel_title .' from '.$check_in_date_read_able. ' to '.$check_out_date_read_able;   ;

      //print_r($current_date);

      if($new_checkout_date_value > $old_checkout_date_value && !empty($hotel_id)) {
        $request = Node::create(['type' => 'user_history']);
        $request->set('title', $message);
        $request->set('field_from_city', $from_city[0]['value']);
        $request->set('field_from_state', $from_state[0]['value']);
        $request->set('field_plan_type', $plan_type[0]['target_id']);
        $request->set('field_room_type', $room_type[0]['target_id']);
        $request->set('field_user_history_hotel', $hotel_id[0]['target_id']);
        $request->set('field_user_id', $uid);
        //$request->set('field_check_in_check_out_details', array($new_checkin_date_value,$new_checkout_date_value));
        //$request->set('field_check_in_check_out_details_end_value', $new_checkout_date_value);
        $request->enforceIsNew();
        $request->save();
      }
      
    }
 

  }
}
