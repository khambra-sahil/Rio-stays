<?php
namespace Drupal\guest_app_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\views\Views;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides a 'Room Information' Block
 *
 * @Block(
 *   id = "room_information",
 *   admin_label = @Translation("Room Information"),
 * )
 */
class RoomInformation extends BlockBase {
  
  public function build() {

    $current_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $hotel_id = $current_user->get('field_hotel')->first()->getValue()['target_id'];

    $node_data = hotel_inventory_data($hotel_id);

    $para = $node_data->field_hotel_rooms_quantity->getValue();

    foreach($para as $key => $value){

        $target_id = $value['target_id'];

        $paragraph = Paragraph::load($target_id);
       	$para_room_type = $paragraph->field_room_type->target_id;

       	$request_term_load = Term::load($para_room_type);

       	$total_room_qty = $paragraph->get('field_room_quantity')->value;

       	$available_room_qty = $paragraph->get('field_remaining_room_quantity')->value;

       	$booked_rooms = $total_room_qty - $available_room_qty;

       	$row_html .= '<div class="columnn">'.$request_term_load->getName().' - '.$available_room_qty.'</div>';       
    }

    $html = '<div class="row">
              '.$row_html.'
            </div>';

    $build = [
      '#markup' => $html,
       '#cache' => [
            'max-age' => 0,
          ]
    ];

    return $build;
  }
}