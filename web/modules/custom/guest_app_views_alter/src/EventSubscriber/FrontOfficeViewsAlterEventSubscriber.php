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
      $hotel_id = $current_user->get('field_hotel')->first()->getValue()['target_id'];

    // Getting view object.
    $view = $event->getView();
    if($view->current_display == 'wifi_details' || $view->current_display == 'help_line_number' ||$view->current_display == 'hotel_number' || $view->current_display == 'hotel_activities' || $view->current_display == 'hotel_rooms_types') {
      
      $query->where[0]['conditions'][0]['value'][':node_field_data_nid'] = $hotel_id;
    }
    else if($view->current_display == 'request_housekeeping' || $view->current_display == 'requests' || $view->current_display == 'report_issue'){
      $query->where[0]['conditions'][0]['value'][':node__field_hotel_field_hotel_target_id'] = $hotel_id;
    }
  }
}