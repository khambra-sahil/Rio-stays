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

      /*$hotel_id = get_hotel_id();
      $room_type_tid = $entity->get('field_upc_user_room_type')->target_id;
      $check_in_start_date = $entity->get('field_user_checkin_checkout_date')->value;
      $check_in_end_date = $entity->get('field_user_checkin_checkout_date')->end_value;
      $available_qty_data = hotel_room_available_qty($hotel_id,$room_type_tid);
      //$term_load = Term::load($room_type_tid);

      if(!empty($available_qty_data)){
        $available_qty = $available_qty_data['remaining_qty'];
      }

      //$rooms = blocked_rooms($hotel_id,$room_type,$check_in_start_date,$check_in_end_date);

      $data = array('nid'=>$entity->id(),'room_type'=>$room_type_tid,'check_in_start_date'=>$check_in_start_date,'check_in_end_date'=>$check_in_end_date,'available_qty'=>$available_qty);

      $url = 'public://upcoming_check_ins/'.'Hotel_id_'.$hotel_id.'.json';
      $tempArray = [];

      if (file_exists($url)) {
        $file_data = json_decode(file_get_contents($url) );
        $flag = false;
        foreach ($file_data as $key => $value) {
          if ($value->nid == $entity->id() ) {
              $flag = true;
              break;
          }
        }
        if(!$flag){
          $tempArray = $file_data;
          array_push($tempArray, $data);
          $jsonData = json_encode($tempArray);
          file_put_contents($url, $jsonData);
        }
      }
      else{
        $file = File::create([
          'filename' => 'Hotel_id_'.$hotel_id.'.json',
          'uri' => $url,
          'status' => 1,
        ]);
        //$file->save();
        $dir = dirname($file->getFileUri());
        if (!file_exists($dir)) {
          mkdir($dir, 0770, TRUE);
        }
        file_put_contents($file->getFileUri(), json_encode(array($data)) );
        $file->save();
      }*/
    }
    /*-----upcoming_check_ins------*/
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
