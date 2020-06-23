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
      $original_check_in_check_out_status = '';
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
        $original_check_in_check_out_status = $entity->original->get('field_check_in_check_out')->value;
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


      //$current_user = \Drupal\user\Entity\User::load($uid);
        $check_in_check_out_status = $entity->get('field_check_in_check_out')->value;

      if($check_in_check_out_status != NULL && $original_check_in_check_out_status != $check_in_check_out_status){

        $hotel_id = $entity->get('field_hotel')->first()->getValue()['target_id'];
        $room_type = $entity->get('field_room_type')->first()->getValue()['target_id'];

        if($room_type != NULL && $hotel_id != NULL){
            
          $hotel_room_data = hotel_room_available_qty($hotel_id,$room_type);
          $available_room_qty = $hotel_room_data['remaining_qty'];
          $paragraph = $hotel_room_data['paragraph'];

          if($check_in_check_out_status == 'check_in' && $available_room_qty >= 1){
            $remaining_room_qty = $available_room_qty - 1;
            $paragraph->set('field_remaining_room_quantity', $remaining_room_qty);
            $paragraph->save();
          }
          else if($original_check_in_check_out_status == 'check_in' && $check_in_check_out_status == 'check_out'){
            $remaining_room_qty = $available_room_qty + 1;
            $paragraph->set('field_remaining_room_quantity', $remaining_room_qty);
            $paragraph->save();
          }
          else{
            //drupal_set_message('Fields are not unique!', 'error');
            drupal_set_message('Error you can not perform this action','error');
          }
        }
        else{
          drupal_set_message('Hotel and Room Type required.','error');
        }

      } 
  
    }

    /*-----requests------*/

    if($entity->bundle() == 'requests'){

      $request_status = $entity->get('field_order_status')->value;
      $original_request_status = $entity->original->get('field_order_status')->value;

      if($request_status == 'revert_inventory' && $original_request_status != $request_status ){

        $hotel_id = $entity->get('field_hotel')->target_id;
        $request = $entity->get('field_sub_services')->value;
        $qauntity = $entity->get('field_quantity')->value;

        /* get tid by term name*/

        $term = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['name' => $request]);
        $term = reset($term);
        $service_id = $term->id();

        $node_data = hotel_inventory_data($hotel_id);

        $para = $node_data->field_quantity_by_service->getValue();

        foreach($para as $key => $value){

          $target_id = $value['target_id'];

          $paragraph = Paragraph::load($target_id);
          $para_service_id = $paragraph->field_service_name->target_id;

          if($para_service_id == $service_id){
              $remaining_qty = $paragraph->field_remaining_qty->value;
              $new_qty = $remaining_qty + $qauntity;
              $paragraph->set('field_remaining_qty', $new_qty);
              $paragraph->save();
          }
        }
      }
    }
    /*-----requests------*/

    /*-----upcoming_check_ins------*/

    if($entity->bundle() == 'upcoming_check_ins'){

      $hotel_id = $entity->get('field_hotel_id')->value;
      if($hotel_id == NULL){
        $current_user_hotel_id = get_hotel_id(); 
        $node = Node::load($entity->id());
        $node->field_hotel_id = [$current_user_hotel_id];
        $node->save();
      }

      $room_type = $entity->get('field_upc_user_room_type')->value;
      $check_in_date = $entity->get('field_user_checkin_checkout_date')->value;

    }

    /*-----upcoming_check_ins------*/

  }
}
