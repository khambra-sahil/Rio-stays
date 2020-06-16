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
class UserFormAlterEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::FORM_ALTER => 'loginFormAlter',
    ];
  }

  /**
   * Implements hook_form_alter().
   *
   * @param array $event
   *   Event array.
   */
  public function loginFormAlter($event)
  {
    $current_form_id = $event->getFormId();
    $form = $event->getForm();
    if ($current_form_id == 'user_form') {
      $allowed_fields = array('field_check_in_checkout_date','field_hotel','field_room_number',
        'field_plan_type','field_room_type','field_from_city','field_from_state','field_first_name','','');
      $current_user = \Drupal::currentUser();
      $roles = $current_user->getRoles();
      if(in_array('front_office', $roles)) {
        $form['account']['#access'] = FALSE;
        $form['user_picture']['#access'] = FALSE;
        $form['contact']['#access'] = FALSE;
        $form['actions']['delete']['#access'] = FALSE;
        //kint($form);exit();
        foreach($form as $key => $value) {
          if(strstr($key, 'field_') &&!in_array($key,$allowed_fields)) {
            $form[$key]['#access'] = FALSE;
          }
        }
      }
    }
    $event->setForm($form);
  }
}
