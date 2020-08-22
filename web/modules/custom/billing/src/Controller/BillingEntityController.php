<?php

namespace Drupal\billing\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\billing\Entity\BillingEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BillingEntityController.
 *
 *  Returns responses for Billing routes.
 */
class BillingEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Billing revision.
   *
   * @param int $billing_entity_revision
   *   The Billing revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($billing_entity_revision) {
    $billing_entity = $this->entityTypeManager()->getStorage('billing_entity')
      ->loadRevision($billing_entity_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('billing_entity');

    return $view_builder->view($billing_entity);
  }

  /**
   * Page title callback for a Billing revision.
   *
   * @param int $billing_entity_revision
   *   The Billing revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($billing_entity_revision) {
    $billing_entity = $this->entityTypeManager()->getStorage('billing_entity')
      ->loadRevision($billing_entity_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $billing_entity->label(),
      '%date' => $this->dateFormatter->format($billing_entity->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Billing.
   *
   * @param \Drupal\billing\Entity\BillingEntityInterface $billing_entity
   *   A Billing object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(BillingEntityInterface $billing_entity) {
    $account = $this->currentUser();
    $billing_entity_storage = $this->entityTypeManager()->getStorage('billing_entity');

    $langcode = $billing_entity->language()->getId();
    $langname = $billing_entity->language()->getName();
    $languages = $billing_entity->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $billing_entity->label()]) : $this->t('Revisions for %title', ['%title' => $billing_entity->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all billing revisions") || $account->hasPermission('administer billing entities')));
    $delete_permission = (($account->hasPermission("delete all billing revisions") || $account->hasPermission('administer billing entities')));

    $rows = [];

    $vids = $billing_entity_storage->revisionIds($billing_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\billing\BillingEntityInterface $revision */
      $revision = $billing_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $billing_entity->getRevisionId()) {
          $link = $this->l($date, new Url('entity.billing_entity.revision', [
            'billing_entity' => $billing_entity->id(),
            'billing_entity_revision' => $vid,
          ]));
        }
        else {
          $link = $billing_entity->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.billing_entity.translation_revert', [
                'billing_entity' => $billing_entity->id(),
                'billing_entity_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.billing_entity.revision_revert', [
                'billing_entity' => $billing_entity->id(),
                'billing_entity_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.billing_entity.revision_delete', [
                'billing_entity' => $billing_entity->id(),
                'billing_entity_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['billing_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
