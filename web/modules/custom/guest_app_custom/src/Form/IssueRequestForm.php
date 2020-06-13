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
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Contribute form.
 */
class IssueRequestForm extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'issue_request_form';
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

    $values['other'] = 'Any Other Specify';

    if(\Drupal::request()->query->get('id') == 152){

      $form['datetime'] = [
        '#type' => 'datetime',
        '#size' => 20,
        '#title' => $this->t('Set Date and Time for Alarm'),
        '#date_date_format' => 'd/m/Y',
        '#date_time_format' => 'H:i',
        '#default_value' => '00:00',
        '#required' => TRUE,
        '#description' => t('Date dd/mm/yyyy and time in 10:30:00 am/pm'),
      ];

    }
    else if(!empty($result)){
      $form['final_services'] = array(
        '#type' => 'select',
        '#title' => $this->t('Request'),
        '#options' => $values,
        '#description' => t('Select a service'),
      );

      $form['user_comment'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Specify Issue'),
      );
    }
    else{

      $request_term =  \Drupal::request()->query->get('id');
      $sub_service_term_load = Term::load($request_term);
      $term_name = $sub_service_term_load->getName();

      $form['sub_services'] = array(
        '#type' => 'textfield',
        '#default_value' => $term_name,
        '#attributes' => array('readonly' => 'readonly'),
        '#title' => $this->t('You have selected')
      );

      $form['user_comment'] = array(
        '#type' => 'textarea',
        '#required' => TRUE,
        '#title' => $this->t('Reasons/Comment/Special request'),
      );
    }

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
     $final_service = $form_state->getValue('final_services');

     if($final_service == 'other'){
      $form_state->setErrorByName('user_comment', t('Please specify the issue.'));
     }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $final_service_term = $form_state->getValue('final_services');
    $user_comment = $form_state->getValue('user_comment');

    $datetime = $form_state->getValue('datetime');

    if($final_service_term !=""){
      $final_service_term_load = Term::load($final_service_term);
    }

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
    if($final_service_term !=""){
      $request->set('field_sub_services', $final_service_term_load->getName());
    }
    $request->set('field_room_number', $room_number);
    $request->set('field_hotel', $hotel);
    if($datetime !=""){
      $timestamp = strtotime($datetime);
      $date = DrupalDateTime::createFromTimestamp($timestamp, 'UTC');
      $request->set('field_service_date_time', $date->format("Y-m-d\TH:i:s"));
    }
    $request->set('field_user_comments', $user_comment);
    $request->enforceIsNew();
    $request->save();

    if($datetime !=""){
      drupal_set_message($this->t('Thank you for your request. Your alarm is set for ').$datetime );
    }
    else{
      drupal_set_message($this->t('Thank you for your request. you request will be served in 20 minutes.'));
    }
  }
}
