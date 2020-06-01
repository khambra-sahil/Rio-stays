<?php

/**
 * @file
 * Contains \Drupal\guest-app-custom\Form\CustomForm.
 */

namespace Drupal\guest_app_otp_login\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\redirect\Entity\Redirect;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;

/**
 * Contribute form.
 */
class UsernameForm extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'username_otp_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['otp_email'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Please enter the Email'),
      '#required' => True
    );
    $form['actions'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $email = $form_state->getValue('otp_email');
    $account = user_load_by_mail($email);
    if(empty($account)) {
      $message = 'The email does not exist';
      $form_state->setErrorByName('otp_email', t($message));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $email = $form_state->getValue('otp_email');
    $account = user_load_by_mail($email);

    if(!empty($account)) {
      //forgot_password_temp_pass_token($account);
      ####################################################
      // OK now lets make a random password.
      //$random_string = Crypt::hmacBase64(4);
      $random_string = random_int(100000, 999999);
      // Create a temp store.
      $service = \Drupal::service('tempstore.shared');
      $collection = 'rest_password';
      // Yep use "get" to set it up.
      $temp_store = $service->get($collection, $account->id());
      $temp_store->set('temp_pass', $random_string);

      ####################################################
      $first_name = $account->get('field_first_name')->first()->getValue();
      $last_name = $account->get('field_last_name')->first()->getValue();
      $mobile = $account->get('field_phone_number')->first()->getValue();
      $first_two_digits = substr($mobile['value'], 0, 2);
      $last_two_digits = substr($mobile['value'], -2);
      $message = $first_name['value'].' '.$last_name['value'].' OTP sent on mobile no. '. $first_two_digits.'x-xxx-xx'.$last_two_digits;
      $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'guest_app_otp_login';
      $key = 'send_otp';
      $to = $email;
      $params['message'] = 'Your OTP to login is: '.$random_string;
      $params['otp_subject'] = $first_name['value'].' '.$last_name['value']. ' Guest APP OTP';
      $lang_code = $account->getPreferredLangcode();
      $send = true;
      $result = $mailManager->mail($module, $key, $to, $lang_code, $params, NULL, $send);
    }
    \Drupal::messenger()->addMessage(t($message), 'status');
    //drupal_set_message($this->t($message));
    $value = $account->id() + 55;
    $url = Url::fromUri('internal:/enter-otp');
    $link_options = array('query' => array('value' => $value));
    $url->setOptions($link_options);
    $destination = $url->toString();
    $response = new RedirectResponse($destination);
    $response->send();
  }

  public function forgot_password_temp_pass_token($account) {
    // OK now lets make a random password.
    //$random_string = Crypt::hmacBase64(4);
    $random_string = random_int(100000, 999999);
    // Create a temp store.
    $service = \Drupal::service('tempstore.shared');
    $collection = 'rest_password';
    // Yep use "get" to set it up.
    $temp_store = $service->get($collection, $account->id());
    $temp_store->set('temp_pass', $random_string);
    return $random_string;
  }
}
