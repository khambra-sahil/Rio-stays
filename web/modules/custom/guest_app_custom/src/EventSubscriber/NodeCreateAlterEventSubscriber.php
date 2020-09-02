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
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::ENTITY_PRE_SAVE => 'nodeCreateAlter',
    ];
  }

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
      //assign hotel id at time of node creation
      $hotel_id = $entity->get('field_ki_hotel_id')->value;
      if($hotel_id == NULL){
        $current_user_hotel_id = get_hotel_id(); 
        $entity->field_ki_hotel_id = [$current_user_hotel_id];
      }

      $current_path = \Drupal::service('path.current')->getPath();

      if (strpos($current_path,'admin/commerce') == false) {
        
        //Store data in custom table

        if($entity->original != NULL) {
          $item_quantity_og = $entity->original->get('field_item_quantity')->value;
          $item_unit_og = $entity->original->get('field_item_unit')->value;
          $vendor_name_og = $entity->original->get('field_vendor_name')->value;
          $item_price_og = $entity->original->get('field_current_item_price')->value;
        }

        $node_id = $entity->id();
        $hotel_id = $entity->get('field_ki_hotel_id')->value;
        $item_name = $entity->get('title')->value;
        $item_quantity = $entity->get('field_item_quantity')->value;
        $item_unit = $entity->get('field_item_unit')->value;
        $vendor_name = $entity->get('field_vendor_name')->value;
        $item_price = $entity->get('field_current_item_price')->value;

        // calculate price from newly added items
        if($entity->original == NULL){
            if($item_unit =='grams' || $item_unit =='ml'){
                $price_for_added_item = ($item_quantity/1000)*$item_price;
            }
            else{
              $price_for_added_item = $item_quantity*$item_price;
            }
            $new_added_item_qty = $item_quantity;
            $new_added_item_unit = $item_unit;
        }

        // if anything change in inventory then update database
        if( ($item_quantity_og != $item_quantity) || ($item_unit_og != $item_unit) || ($vendor_name_og != $vendor_name) ||  ($item_price_og != $item_price) ){
          // calculate price from newly added items for existing item
          if($item_unit == $item_unit_og ){
            $new_quantity = $item_quantity - $item_quantity_og;
              if($item_unit =='grams' || $item_unit =='ml'){
                $price_for_added_item = ($new_quantity/1000)*$item_price;
              }
              else{
                $price_for_added_item = $new_quantity*$item_price;
              }
              $new_added_item_qty = $new_quantity;
              $new_added_item_unit = $item_unit;
          }
          if( ($item_unit_og == 'grams' && $item_unit == 'kg') || ($item_unit_og == 'ml' && $item_unit == 'litre') ){
            $item_quantity_og = $item_quantity_og / 1000;
            $new_quantity = $item_quantity - $item_quantity_og;
            $price_for_added_item = $new_quantity*$item_price;

            $new_added_item_qty = $new_quantity;
            $new_added_item_unit = $item_unit;
          }
        }
        $db = \Drupal::database()->insert('kitchen_inventory_log')
              ->fields(['node_id','hotel_id','item_name','old_quantity','old_unit','old_vendor','old_item_price','new_quantity','new_unit','new_vendor','new_item_price','new_added_item_qty','new_added_item_unit','price_for_added_item'])
              ->values(array($node_id, $hotel_id, $item_name, $item_quantity_og, $item_unit_og, $vendor_name_og, $item_price_og, $item_quantity, $item_unit, $vendor_name, $item_price, $new_added_item_qty, $new_added_item_unit, $price_for_added_item))
              ->execute();
      }
      
    }

  }
    
}
