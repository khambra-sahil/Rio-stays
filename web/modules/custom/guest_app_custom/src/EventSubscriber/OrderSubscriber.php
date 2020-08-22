<?php

namespace Drupal\guest_app_custom\EventSubscriber;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;
use Drupal\paragraphs\Entity\Paragraph;

class OrderSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.place.post_transition' => ['onPlace',-101],
    ];
  }
  /**
   * on order place decrease kitchen inventory based order item
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onPlace(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    foreach ($order->getItems() as $order_item) {
      $item_quantity = $order_item->getQuantity();

      $product_variation = $order_item->getPurchasedEntity();
      $ingredients = $product_variation->field_item_ingredients->getValue();

      if(!empty($ingredients)){

        foreach ($ingredients as $key => $value) {
          $target_id = $value['target_id'];
          $paragraph = Paragraph::load($target_id);
          $ingredient_id = $paragraph->field_item_name->target_id;
          $ingredient_qty = $paragraph->field_item_quantity->value;
          $ingredient_unit = $paragraph->field_item_unit->value;

          //load kitchen inventory
          $itm = Node::load($ingredient_id);
          $inventory_qty = $itm->get('field_item_quantity')->value;
          $inventory_unit = $itm->get('field_item_unit')->value;

          if($ingredient_unit == $inventory_unit){
            $final_qty = $inventory_qty - ( $ingredient_qty * $item_quantity);
            $final_unit = $ingredient_unit;
          }
          else{
            //for KG
            if(strtolower($inventory_unit) == 'kg'){
              $inventory_qty = $inventory_qty * 1000;
              $final_qty = $inventory_qty - ( $ingredient_qty * $item_quantity);
              if($final_qty < 1000){
                $final_unit = 'grams';
              }
              else{
                $final_qty = $final_qty/1000;
                $final_unit = 'kg';
              }
            }
            //for Litre
            if(strtolower($inventory_unit) == 'litre'){
              $inventory_qty = $inventory_qty * 1000;
              $final_qty = $inventory_qty - ( $ingredient_qty * $item_quantity);
              if($final_qty < 1000){
                $final_unit = 'ml';
              }
              else{
                $final_qty = $final_qty/1000;
                $final_unit = 'litre';
              }
            }

          }
          $itm->set('field_item_quantity', $final_qty);
          $itm->set('field_item_unit', $final_unit);
          $itm->save();
        }

      }
      
    }
  }

}