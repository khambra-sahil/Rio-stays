<?php

/**
 * @file
 * Contains billing_entity.page.inc.
 *
 * Page callback for Billing entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Billing templates.
 *
 * Default template: billing_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_billing_entity(array &$variables) {
  // Fetch BillingEntity Entity Object.
  $billing_entity = $variables['elements']['#billing_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
