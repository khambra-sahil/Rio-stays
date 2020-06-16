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
class DirectRequestForm extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'direct_request_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $request_term =  \Drupal::request()->query->get('id');
    $sub_service_term_load = Term::load($request_term);
    $tearm_name = $sub_service_term_load->getName();
    
    $form['final_services'] = array(
      '#type' => 'textfield',
      '#default_value' => $tearm_name,
      '#attributes' => array('readonly' => 'readonly'),
      '#title' => $this->t('You have selected')
    );

    $form['actions'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Confirm Submit Above Request'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
  
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $sub_service_term = \Drupal::request()->query->get('id');
    $sub_service_term_load = Term::load($sub_service_term);
    $request_term = key(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($sub_service_term));
    $request_term_load = Term::load($request_term);
    $current_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

    $username = $current_user->get('name')->value;
    $room_number = $current_user->get('field_room_number')->value;
    $hotel = $current_user->get('field_hotel')->first()->getValue()['target_id'];

    // save request in node
    $request = Node::create(['type' => 'requests']);
    $request->set('title', 'Request of ' . $sub_service_term_load->getName() . ' from room number ' . $room_number);
    $request->set('field_request', $request_term_load->getName());
    $request->set('field_service_type', $sub_service_term_load->getName());
    $request->set('field_room_number', $room_number);
    $request->set('field_hotel', $hotel);
    $request->enforceIsNew();
    $request->save();

    drupal_set_message($this->t('Thank you for your request. you request will be served in 20 minutes.'));
  }
}
