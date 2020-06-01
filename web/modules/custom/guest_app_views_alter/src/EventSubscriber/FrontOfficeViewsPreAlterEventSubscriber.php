<?php

namespace Drupal\guest_app_views_alter\EventSubscriber;

use Drupal\Core\Url;
use Drupal\hook_event_dispatcher\Event\Views\ViewsPreRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Views\ViewsPreBuildEvent;

/**
 * Event subscriber class which helps to alter the views Query.
 */
class FrontOfficeViewsPreAlterEventSubscriber implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::VIEWS_PRE_RENDER => 'preRender'
    ];
  }

  /**
   * Callback function for ViewsQueryAlter.
   * @param $event
   */
  public function preRender(ViewsPreRenderEvent $event)
  {
    // Getting view object.
    $view = $event->getView();
    //$view->field['name']->options['alter']['path'] =
    //kint($view); exit();
  }
}
