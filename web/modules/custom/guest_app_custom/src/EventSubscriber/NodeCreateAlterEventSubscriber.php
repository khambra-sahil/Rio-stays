<?php

namespace Drupal\guest_app_custom\EventSubscriber;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;

use Drupal\node\NodeInterface;
/**
 * Class ExampleEntityEventSubscribers.
 *
 * Don't forget to define your class as a service and tag it as
 * an "event_subscriber":
 *
 * services:
 *   guest_app_custom.nodeCreateAlter:
 *   class:'\Drupal\guest_app_custom\EventSubscriber\NodeCreateAlterEventSubscriber'
 *   tags:
 *     - { name: 'event_subscriber' }
 */
class NodeCreateAlterEventSubscriber implements EventSubscriberInterface {

  /**
   * Entity pre save.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent $event
   *   The event.
   */
  public function nodeCreateAlter(EntityPresaveEvent $event) {
    // Do some fancy stuff with new entity.
    $entity = $event->getEntity();
    
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
        $entity->field_hotel_id = [$current_user_hotel_id];
      }
      if($entity->original != NULL) {
        $original_booking_status = $entity->original->get('field_booking_status')->value;
      }
      $booking_status = $entity->get('field_booking_status')->value;

      // revert rooms inventory in json file if upcoming check-in is cancelled
      if($booking_status == 3 && $original_booking_status != $booking_status){
        $room_type_tid = $entity->get('field_upc_user_room_type')->target_id;
        $check_in_start_date = get_date_timestamp($entity->get('field_user_checkin_checkout_date')->value);
        $check_in_end_date = get_date_timestamp($entity->get('field_user_checkin_checkout_date')->end_value);

        for ($i=$check_in_start_date; $i<$check_in_end_date; $i+=86400) {
          $url = 'public://upcoming_check_ins/'.'Hotel_id_'.$hotel_id.'.json';
          $file_data = json_decode(file_get_contents($url) );
          $flag = false;
          foreach ($file_data as $key => $value) {
            if ($value->room_type == $room_type_tid 
              && ( date("Y-m-d", $i) == date('Y-m-d',$value->check_in_start_date)) 
              && ( date("Y-m-d", $i+86400) == date('Y-m-d',$value->check_in_end_date)) 
              ){
                  $file_data[$key]->available_qty = ($value->available_qty)+1;
                   $jsonData = json_encode($file_data);
                  file_put_contents($url, $jsonData);
                break;
            }
          }
        }
      }
  
    }
    /*-----upcoming_check_ins------*/

    if($entity->bundle() == 'kitchen_inventory'){
      $hotel_id = $entity->get('field_ki_hotel_id')->value;
      if($hotel_id == NULL){
        $current_user_hotel_id = get_hotel_id(); 
        $entity->field_ki_hotel_id = [$current_user_hotel_id];
      }

      //Store data in custom table

    }
    
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::ENTITY_PRE_SAVE => 'nodeCreateAlter',
    ];
  }

}
