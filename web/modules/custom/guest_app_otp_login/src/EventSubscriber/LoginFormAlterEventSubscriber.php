<?php

namespace Drupal\guest_app_otp_login\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;

/**
 * The class is used to implement event for form alter.
 *
 * @package Drupal\etisc_form_alter\EventSubscriber
 */
class LoginFormAlterEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      //HookEventDispatcherInterface::FORM_ALTER => 'loginFormAlter',
    ];
  }

  /**
   * Implements hook_form_alter().
   *
   * @param array $event
   *   Event array.
   */
  public function loginFormAlter($event) {
    $current_form_id = $event->getFormId();
	$form = $event->getForm();
	//kint($form); exit();

    if ($current_form_id == 'user_login_form') {
      unset($form['pass']);
	  unset($form['actions']);
	  unset($form['#validate']);
	  $form['actions']['submit'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Next'),
        // Custom submission handler for page 1.
        '#submit' => ['redirecToOtp'],
        // Custom validation handler for page 1.
        //'#validate' => ['::fapiExampleMultistepFormNextValidate'],
      ];
    }
    $event->setForm($form);
  }
  public function redirecToOtp(array &$form, FormStateInterface $form_state) {
	exit();
	return new RedirectResponse(Drupal\Core\Url::fromUri('otp_request.form'));
  }
}
