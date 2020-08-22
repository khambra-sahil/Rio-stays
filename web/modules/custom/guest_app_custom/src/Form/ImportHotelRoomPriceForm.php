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
use Drupal\paragraphs\Entity\Paragraph;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

use \Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Contribute form.
 */
class ImportHotelRoomPriceForm extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'import_hotel_room_price';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  
    $form = array(
      '#attributes' => array('enctype' => 'multipart/form-data'),
    );
    
    $form['file_upload_details'] = array(
      '#markup' => t('<b>Hotel Room Price File</b>'),
    );
  
    $validators = array(
      'file_validate_extensions' => array('csv'),
    );
    $form['excel_file'] = array(
      '#type' => 'managed_file',
      '#name' => 'excel_file',
      '#title' => t('File *'),
      '#size' => 20,
      '#description' => t('Excel format only'),
      '#upload_validators' => $validators,
      '#upload_location' => 'public://data_upload/csv_files/',
    );
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    );
 
    return $form;
 
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) { 

    global $base_url;

    if ($form_state->getValue('excel_file') == NULL) {
      $form_state->setErrorByName('excel_file', $this->t('upload proper File'));
    }
    else{
      $rows = csvFileRowData($form_state->getValue('excel_file')[0]);

      if(isset($rows[1][1])){
        //Check data is already exist for this hotel
        $hotel_id = getNidByTitle($rows[1][1]);

        if($hotel_id == ""){
           $form_state->setErrorByName('Hotel Name Not found', $this->t('Hotel name you entered is not found in our database.'));
        }

        $entity_ids = \Drupal::entityQuery('node')
        ->condition('type', 'hotel_room_price')
        ->condition('field_hrp_hotel_name', $hotel_id)
        ->execute();
        $hotel_nid = reset($entity_ids);//fetch first record in case of multiple
        if(isset($hotel_nid) && $hotel_nid != ""){

          $message = '<a href="'.$base_url.'/node/'.$hotel_nid.'/edit">Here</a>';
          $rendered_message = \Drupal\Core\Render\Markup::create($message);
          $error_message = new TranslatableMarkup ('@message', array('@message' => $rendered_message));

          $form_state->setErrorByName('Data Already Exist!', $this->t('Price data for this node is already Exist, you can modify the data from node edit page from ').$error_message);
        }
        else{
          $i = 1;
          foreach($rows as $row=>$value){
            if($i > 1){
              if(isset($value[2]) && isset($value[3]) && isset($value[4]) && isset($value[5]) ){
                $room_type = $value[2];
                $plan_type = $value[4];
                if( !getTidByName($room_type) > 0 || !getTidByNameSp($plan_type) > 0){
                  $form_state->setErrorByName('Incorrect Data', $this->t('Data is not correct in row number ').$i.' Please check the data and Upload it again.');
                }
              }
              else{
                $form_state->setErrorByName('Incorrect Data', $this->t('Data is not correct or missing in row number ').$i.' Please check the data and Upload it again.');
              }
            }
            $i++;
          }
        }
      }
      else{
        $form_state->setErrorByName('Hotel Name Not found', $this->t('Hotel name is missing.'));
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state){ 

    global $base_url;

    $rows = csvFileRowData($form_state->getValue('excel_file')[0]);

    $node_title = $rows[1][0];
    $hotel_id = getNidByTitle($rows[1][1]);

    $para_arr = array();
    $i = 1;
    foreach($rows as $row => $value){
      if($i > 1){
        $room_type = getTidByName($value[2]);
        $occupancy = $value[3];
        $plan_type = getTidByNameSp($value[4]);
        $price = $value[5];

        $paragraph = Paragraph::create([
            'type' => 'hotel_room_price',
            'field_hrp_room_type' => ['target_id' => $room_type],
            'field_occupancy' => ['value' => $occupancy],
            'field_hrp_stay_plan' => ['target_id' => $plan_type],
            'field_price' => ['value' => $price]
        ]);
        $paragraph->save();
        $para_arr[] = array('target_id'=>$paragraph->id(),'target_revision_id'=>$paragraph->getRevisionId());
      }
      $i++;
    }

    $nodeData = [
        'type' => 'hotel_room_price',
        'status' => 1,
        'title' => $node_title,
        'field_hrp_hotel_name' => ['target_id' => $hotel_id],
        'field_hotel_room_price' => $para_arr,
    ];

    $entity = Node::create($nodeData);
    $entity->save();
    //insert data in node
    \Drupal::messenger()->addMessage('Data imported successfully for '.$node_title.' You can check the content "<a href='.$base_url.'/node/'.$hotel_id.'/edit>Here</a>"');
  }

}