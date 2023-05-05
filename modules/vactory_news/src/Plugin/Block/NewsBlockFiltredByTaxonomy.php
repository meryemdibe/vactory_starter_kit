<?php

namespace Drupal\vactory_news\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Vactory News Filtred By Taxonomy Block " block.
 *
 * @Block(
 *   id = "vactory_vactory_news_block_filtred_by_taxonomy",
 *   admin_label = @Translation("Vactory News Block Filtred By Taxonomy"),
 *   category = @Translation("Vactory")
 * )
 */
class NewsBlockFiltredByTaxonomy extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'news_block_selected' => '',
      'filtred_term' => '',
      'override_more_link' => '',
    ];
  }

  /**
   * Block form function.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);
    $news_view = $this->entityTypeManager->getStorage('view')->load('vactory_news');
    $news_blocks = [];
    if (isset($news_view) && !empty($news_view)) {
      $news_displays = $news_view->get('display');
      if (!empty($news_displays)) {
        foreach ($news_displays as $display) {
          if ($display['display_plugin'] == 'block' && isset($display['display_options']['arguments']) && !empty($display['display_options']['arguments'])) {
            $news_blocks[$display['id']] = $display['display_title'];
          }
        }
      }
    }
    $news_term = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'vactory_news_theme']);
    $terms = [];
    if (isset($news_term) && !empty($news_term)) {
      foreach ($news_term as $key => $term) {
        $terms[$key] = $term->get('name')->value;
      }
    }
    $form['news_block'] = [
      '#type' => 'select',
      '#title' => $this->t('Blocks News'),
      '#options' => $news_blocks,
      '#default_value' => $this->configuration['news_block_selected'],
      '#required' => TRUE,
    ];

    $form['filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Thématique News'),
      '#options' => $terms,
      '#default_value' => $this->configuration['filtred_term'],
      '#required' => TRUE,
    ];
    $form['override_more_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Appliquer le filtre par taxonomie sur le lien Voir plus du bloc'),
      '#description' => $this->t('Si coché alors le lien voir plus du bloc sera surchargé de façon à appliquer un filtre par le term de taxonomy choisi'),
      '#default_value' => $this->configuration['override_more_link'],
    ];
    return $form;
  }

  /**
   * Block submit function.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['news_block_selected'] = $form_state->getValue('news_block');
    $this->configuration['filtred_term'] = $form_state->getValue('filter');
    $this->configuration['override_more_link'] = $form_state->getValue('override_more_link');
  }

  /**
   * Build function.
   */
  public function build() {
    $news_block_id = $this->configuration['news_block_selected'];
    $news_term_id = $this->configuration['filtred_term'];
    $override_more_link = $this->configuration['override_more_link'];
    $children = $this->entityTypeManager->getStorage('taxonomy_term')->loadChildren($news_term_id);
    $selected_news_term_id = $news_term_id;
    if (!empty($children)) {
      $children_ids = array_keys($children);
      $children_ids[] = $news_term_id;
      $news_term_id = implode('+', $children_ids);
    }
    return [
      '#theme' => 'vactory_news_block_filtred_by_taxonomy',
      '#content'  => [
        'news_id' => $news_term_id,
        'news_block_id' => $news_block_id,
        'meta_data' => [
          'override_more_link' => $override_more_link,
          'field_name' => 'field_vactory_news_theme',
          'news_term_id' => $selected_news_term_id,
        ],
      ],
      '#cache' => [
        // Set the caching policy to match the default block caching policy.
        'max-age' => 0,
        'contexts' => ['url'],
        'tags' => ['rendered'],
      ],
    ];
  }

}
