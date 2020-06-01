<?php

/**
     * @file
     * Contains \Drupal\guest-app-custom\Form\CustomForm.
     */

namespace Drupal\guest_app_otp_login\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\views\Views;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\redirect\Entity\Redirect;
use Symfony\Component\HttpFoundation\RedirectResponse;
/**
 * Contribute form.
 */
class OTPForm extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'otp_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['otp'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Please enter the OTP')
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
    $otp = $form_state->getValue('otp');
    $arg = \Drupal::request()->query->get('value');
    $actual_id = $arg - 55;
    $account = \Drupal\user\Entity\User::load($actual_id);
    $uid = $account->id();
    $service = \Drupal::service('tempstore.shared');
    $collection = 'rest_password';
    $tempstore = $service->get($collection, $uid);
    $sent_otp = $tempstore->getIfOwner('temp_pass');
    if($sent_otp != $otp) {
      $message = 'The given OTP is not correct';
      $form_state->setErrorByName('otp_username', t($message));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $arg = \Drupal::request()->query->get('value');
    $actual_id = $arg - 55;
    $account = \Drupal\user\Entity\User::load($actual_id);
    if(isset($account)) {
      $first_name = $account->get('field_first_name')->first()->getValue();
      $last_name = $account->get('field_last_name')->first()->getValue();
      if (!$account->get('field_hotel')->isEmpty()) {
        $hotel_id = $account->get('field_hotel')->first()->getValue();
        $hotel_load = \Drupal\node\Entity\Node::load($hotel_id['target_id']);
        $message = $first_name['value'] . ' ' . $last_name['value'] . ' welcome to the Hotel ' . $hotel_load->getTitle() . '.';
      } else {
        $message = $first_name['value'] . ' ' . $last_name['value'] . ' you are logged in';
      }
      drupal_set_message($this->t($message));
      user_login_finalize($account);
      $response = new RedirectResponse('/node/1');
      $response->send();
    }
  }

}
