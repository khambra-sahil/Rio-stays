<?php

/**
 * @file
 * Contains \Drupal\guest-app-custom\Form\CustomForm.
 */

namespace Drupal\guest_app_custom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\views\Views;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;

/**
 * Contribute form.
 */
class RequestForm extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'request_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $arg[] = \Drupal::request()->query->get('id');
    $view = Views::getView('services');
    $view->setArguments($arg);
    $view->setDisplay('page_3');
    $view->preExecute();
    $view->execute();
    $result = $view->result;
    foreach ($result as $data => $value) {
      $tid = $value->tid;
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($value->tid);
      $values[$value->tid] = $term->getname();
    }
    $form['final_services'] = array(
      '#type' => 'select',
      '#title' => $this->t('Request'),
      '#options' => $values,
      '#description' => t('Select a service'),
    );
    $form['quantity'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Please enter the quantity')
    );
    $form['actions'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $final_service_term = $form_state->getValue('final_services');
    $final_service_term_load = Term::load($final_service_term);
    $allowed_quantity = $final_service_term_load->get('field_quantity')->value;
    $quantity = $form_state->getValue('quantity');
    if ($quantity > $allowed_quantity) {
      $form_state->setErrorByName('quantity', t('Maximum quantity can be ordered ' . $allowed_quantity));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $quantity = $form_state->getValue('quantity');
    $final_service_term = $form_state->getValue('final_services');
    $final_service_term_load = Term::load($final_service_term);
    //kint($form_state->getValue('final_services')); exit();
    $sub_service_term = \Drupal::request()->query->get('id');
    $sub_service_term_load = Term::load($sub_service_term);
    $request_term = key(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($sub_service_term));
    $request_term_load = Term::load($request_term);
    $current_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

    $username = $current_user->get('name')->value;
    $room_number = $current_user->get('field_room_number')->value;
    $hotel = $current_user->get('field_hotel')->first()->getValue()['target_id'];

    $request = Node::create(['type' => 'requests']);
    $request->set('title', 'Request of ' . $sub_service_term_load->getName() . ' from room number ' . $room_number);
    $request->set('field_quantity', $quantity);
    $request->set('field_request', $request_term_load->getName());
    $request->set('field_service_type', $sub_service_term_load->getName());
    $request->set('field_sub_services', $final_service_term_load->getName());
    $request->set('field_room_number', $room_number);
    $request->set('field_room_number', $room_number);
    $request->set('field_hotel', $hotel);
    $request->enforceIsNew();
    $request->save();
    drupal_set_message($this->t('Thank you for your request. you request will be served in 20 minutes.'));
  }
}
