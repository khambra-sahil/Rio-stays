<?php

namespace Drupal\guest_app_views_alter\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Views\ViewsQueryAlterEvent;



/**
 * Event subscriber class which helps to alter the views Query.
 */
class FrontOfficeViewsAlterEventSubscriber implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::VIEWS_QUERY_ALTER => 'ViewsQueryAlter'
    ];
  }

  /**
   * Callback function for ViewsQueryAlter.
   * @param $event
   */
  public function ViewsQueryAlter(ViewsQueryAlterEvent $event )
  {
    // Getting Query object.
      $query = $event->getQuery();
      $current_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
      if(\Drupal::currentUser()->id() != 0){
        $hotel_id = $current_user->get('field_hotel')->first()->getValue()['target_id'];
      }
      
    // Getting view object.
    $view = $event->getView();

    $hotel_views = ['wifi_details','help_line_number','hotel_number','hotel_activities','hotel_rooms_types'];
    $requests_views = ['request_housekeeping','requests','report_issue','housekeeping_export','request_export','report_issue_export'];
    $upcoming_checkins_views = ['upcoming_checkins','future_checkins','upcoming_checkins_export','future_check_ins_export'];
    $booking = ['confirm_checkins','cancelled_bookings','confirm_booking_export','cancelled_bookings_export'];
    $user_history = ['checkout_log','user_history'];
    $hotel_inventory = ['rooms_inventory','service_inventory','rooms_inventory_export','quantity_by_servise_export'];
    $event_booking = ['event_booking','event_booking_data_export'];

    if(!empty($hotel_id) && in_array($view->current_display, $hotel_views)) {
      
      $query->where[0]['conditions'][0]['value'][':node_field_data_nid'] = $hotel_id;
    }

    if(!empty($hotel_id) && in_array($view->current_display, $requests_views)){
      $query->where[0]['conditions'][0]['value'][':node__field_hotel_field_hotel_target_id'] = $hotel_id;
    }
    
   if(!empty($hotel_id) && $view->current_display == 'client'){
      $query->where[0]['conditions'][0]['value'][':user__field_hotel_field_hotel_target_id'] = $hotel_id;
    }

    if(!empty($hotel_id) && in_array($view->current_display, $upcoming_checkins_views)){
      $query->where[0]['conditions'][0]['value'] = $hotel_id;
    }

    if(!empty($hotel_id) && in_array($view->current_display, $booking)){
      $query->where[0]['conditions'][1]['value'] = $hotel_id;
    }

    if(!empty($hotel_id) && in_array($view->current_display, $hotel_inventory)){
      $query->where[0]['conditions'][0]['value'][':node__field_hotel_name_field_hotel_name_target_id'] = $hotel_id;
    }

    if(!empty($hotel_id) && in_array($view->current_display, $user_history)){
      $query->where[0]['conditions'][0]['value'][':node__field_user_history_hotel_field_user_history_hotel_target_id'] = $hotel_id;
    }

    if(!empty($hotel_id) && in_array($view->current_display, $event_booking)){
      $query->where[0]['conditions'][0]['value'][':node__field_booking_query_hotel_field_booking_query_hotel_target_id'] = $hotel_id;
    }

  }
}