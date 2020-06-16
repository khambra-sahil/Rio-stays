<?php
namespace Drupal\guest_app_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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

    /*$results = views_get_view_result('contact_us', 'block_1');

    foreach ($results as $key=>$result) {
      
    }*/

    $build = [
      '#markup' => $this->t('This is a room information block!'),
    ];

    return $build;
  }
}