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
class ImportHotelInventoryForm extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'import_hotel_inventory';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  
    $form = array(
      '#attributes' => array('enctype' => 'multipart/form-data'),
    );
    
    $form['file_upload_details'] = array(
      '#markup' => t('<b>Hotel Inventory File</b>'),
    );
  
    $validators = array(
      'file_validate_extensions' => array('csv'),
    );
    $form['excel_file'] = array(
      '#type' => 'managed_file',
      '#name' => 'excel_file',
      '#title' => t('File *'),
      '#size' => 20,
      '#description' => t('Excel format only.'),
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

      if(isset($rows[1][1]) ){
        //Check data is already exist for this hotel
        $hotel_id = getNidByTitle($rows[1][1]);

        if($hotel_id == ""){
           $form_state->setErrorByName('Hotel Name Not found', $this->t('Hotel name you entered is not found in our database.'));
        }

        $entity_ids = \Drupal::entityQuery('node')
        ->condition('type', 'hotel_inventory')
        ->condition('field_hotel_name', $hotel_id)
        ->execute();
        $hotel_nid = reset($entity_ids);//fetch first record in case of multiple
        if(isset($hotel_nid) && $hotel_nid != ""){

          $message = '<a href="'.$base_url.'/node/'.$hotel_nid.'/edit">Here</a>';

          $form_state->setErrorByName('Data Already Exist!', $this->t('Data for this node is already Exist, you can modify the data from node edit page from ').$message);
        }
        else{
          $i = 1;
          foreach($rows as $row=>$value){
            if($i > 1){

              if(isset($value[2])){
                $room_type = $value[2];
                //for rooms inventory
                if( !getTidByName($room_type) > 0 ){
                  $form_state->setErrorByName('Incorrect Data', $this->t('Data is not correct for room type in row number ').$i.' Please check the data and Upload it again.');
                }
                else{
                  if(!isset($value[3]) || !isset($value[4])  || !isset($value[5])  || !isset($value[6]) ){
                    $form_state->setErrorByName('Data Missing', $this->t('Please Enter data in all Field of ').$room_type);
                  }
                  elseif($value[5] > $value[4]){
                    $form_state->setErrorByName('Error', $this->t('Blocked rooms should be less than available rooms, for ').$room_type);
                  }
                  elseif($value[4] > $value[3]){
                    $form_state->setErrorByName('Error', $this->t('Available rooms should be less than or equal to total rooms, for ').$room_type);
                  }
                }
              }

              if(isset($value[7])){
                $service_name = $value[7];
                //for services inventory
                if( !getTidByServiceName($service_name) > 0 ){
                  $form_state->setErrorByName('Incorrect Data', $this->t('Data is not correct for service name in row number ').$i.' Please check the data and Upload it again.');
                }
                else{
                  if(!isset($value[7]) || !isset($value[8]) || !isset($value[9]) ){
                    $form_state->setErrorByName('Data Missing', $this->t('Please Enter data in all Field of ').$service_name);
                  }
                  elseif($value[9] > $value[8]){
                    $form_state->setErrorByName('Error', $this->t('Available Qty should be less than or equal to total Qty, for ').$service_name);
                  }
                }
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

    $rooms_para_arr = array();
    $services_para_arr = array();
    $i = 1;
    foreach($rows as $row => $value){
      if($i > 1){

        //Rooms Inventory
        if(isset($value[2]) && isset($value[3])  && isset($value[4])  && isset($value[5]) && isset($value[6]) ){
          $room_type = getTidByName($value[2]);
          $room_qty = $value[3];
          $room_remaining_qty = $value[4];
          $blocked_room = $value[5];
          $maximum_occupancy = $value[6];

          $rooms_paragraph = Paragraph::create([
            'type' => 'hotel_room_type',
            'field_room_type' => ['target_id' => $room_type],
            'field_room_quantity' => ['value' => $room_qty],
            'field_remaining_room_quantity' => ['value' => $room_remaining_qty],
            'field_blocked_rooms_quantity' => ['value' => $blocked_room],
            'field_maximum_occupancy' => ['value' => $maximum_occupancy]
          ]);
          $rooms_paragraph->save();
          $rooms_para_arr[] = array('target_id'=>$rooms_paragraph->id(),'target_revision_id'=>$rooms_paragraph->getRevisionId());
        }

        //Services Inventory
        if(isset($value[7]) && isset($value[8]) && isset($value[9])){
          $service_name = getTidByServiceName($value[7]);
          $total_qty = $value[8];
          $remaining_qty = $value[9];
        
          $services_paragraph = Paragraph::create([
            'type' => 'quantity_by_service',
            'field_service_name' => ['target_id' => $service_name],
            'field_total_quantity' => ['value' => $total_qty],
            'field_remaining_qty' => ['value' => $remaining_qty],
          ]);
          $services_paragraph->save();
          $services_para_arr[] = array('target_id'=>$services_paragraph->id(),'target_revision_id'=>$services_paragraph->getRevisionId());
        }

      }
      $i++;
    }

    $nodeData = [
        'type' => 'hotel_inventory',
        'status' => 1,
        'title' => $node_title,
        'field_hotel_name' => ['target_id' => $hotel_id],
        'field_hotel_rooms_quantity' => $rooms_para_arr,
        'field_quantity_by_service' => $services_para_arr,
    ];

    $entity = Node::create($nodeData);
    $entity->save();
    //insert data in node
    \Drupal::messenger()->addMessage('Data imported successfully for '.$node_title.' You can check the content "<a href='.$base_url.'/node/'.$hotel_id.'/edit>Here</a>"');
    
  }

}