<?php

namespace Drupal\vactory_rate\Field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\node\Entity\Node;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;

/**
 * Item list for a computed vote field.
 *
 */

class VoteEntityFieldItemList extends FieldItemList
{
  use ComputedItemListTrait;

  protected ?CacheableMetadata $cacheMetadata = NULL;

  protected $rate;

  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL)
  {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->rate = $container->get('vactory_rate.rate');
    $instance->cacheMetadata = new CacheableMetadata();
    return $instance;
  }


  /**
   * {@inheritdoc}
   */
  protected function computeValue()
  {
    /** @var \Drupal\node\NodeInterface $node */
    $entity = $this->getEntity();
    $bundle = $entity->bundle();
    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();
    if ($bundle != 'vactory_academy') {
        return;
    }
    $voting_results = $this->rate->results($entity_type, $entity_id);
    $results = json_decode($voting_results->getContent(),true);
    $this->cacheMetadata->addCacheContexts(['user']);
    $this->cacheMetadata->addCacheTags(['vote_list']);

    $this->list[0] = $this->createItem(0, $results);
  }

  /**
   * {@inheritdoc}  
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    $access = parent::access($operation, $account, TRUE);

    if ($return_as_object) {
      /** @see \Drupal\jsonapi\JsonApiResource\ResourceIdentifier */
      /** @see \Drupal\jsonapi\Normalizer\ResourceIdentifierNormalizer */
      /** @see \Drupal\jsonapi\Normalizer\ResourceObjectNormalizer::serializeField() */
      $this->ensureComputedValue();
      \assert($this->cacheMetadata instanceof CacheableMetadata);
      $access->addCacheableDependency($this->cacheMetadata);

      return $access;
    }

    return $access->isAllowed();
  }

}
