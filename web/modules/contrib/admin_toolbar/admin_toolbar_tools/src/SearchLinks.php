<?php

namespace Drupal\admin_toolbar_tools;

use Drupal\admin_toolbar_tools\Plugin\Derivative\ExtraLinks;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\system\Entity\Menu;

/**
 * Extra search links.
 */
class SearchLinks {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The cache context manager service.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected $cacheContextManager;

  /**
   * The toolbar cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $toolbarCache;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, RouteProviderInterface $route_provider, CacheContextsManager $cache_context_manager, CacheBackendInterface $toolbar_cache) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->routeProvider = $route_provider;
    $this->cacheContextManager = $cache_context_manager;
    $this->toolbarCache = $toolbar_cache;
  }

  /**
   * Get extra links for admin toolbar search feature.
   *
   * @return array
   *   An array of link data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getLinks() {
    $additional_keys = $this->cacheContextManager->convertTokensToKeys([
      'languages:' . LanguageInterface::TYPE_INTERFACE,
      'user.permissions',
    ])->getKeys();
    $cid_parts = array_merge(['admin_toolbar_search:links'], $additional_keys);
    $cid = implode(':', $cid_parts);

    if ($cache = $this->toolbarCache->get($cid)) {
      return $cache->data;
    }

    $links = [];
    $cache_tags = [];
    $content_entities = $this->getBundleableEntitiesList();

    // Adds common links to entities.
    foreach ($content_entities as $entities) {
      $content_entity_bundle = $entities['content_entity_bundle'];
      $content_entity = $entities['content_entity'];
      // Start at offset 10, since the toolbar has already loaded the first 10.
      $content_entity_bundle_storage = $this->entityTypeManager->getStorage($content_entity_bundle);
      $bundles_ids = $content_entity_bundle_storage->getQuery()->range(ExtraLinks::MAX_BUNDLE_NUMBER)->execute();
      if (!empty($bundles_ids)) {
        $bundles = $this->entityTypeManager
          ->getStorage($content_entity_bundle)
          ->loadMultiple($bundles_ids);
        foreach ($bundles as $machine_name => $bundle) {
          $cache_tags = Cache::mergeTags($cache_tags, $bundle->getEntityType()->getListCacheTags());
          $tparams = [
            '@entity_type' => $bundle->getEntityType()->getLabel(),
            '@bundle' => $bundle->label(),
          ];
          $label_base = $this->t('@entity_type > @bundle', $tparams);
          $params = [$content_entity_bundle => $machine_name];
          if ($this->routeExists('entity.' . $content_entity_bundle . '.overview_form')) {
            // Some bundles have an overview/list form that make a better root
            // link.
            $url = Url::fromRoute('entity.' . $content_entity_bundle . '.overview_form', $params);
            $url_string = $url->toString();
            $links[] = [
              'labelRaw' => $label_base,
              'value' => $url_string,
            ];
          }
          if ($this->routeExists('entity.' . $content_entity_bundle . '.edit_form')) {
            $url = Url::fromRoute('entity.' . $content_entity_bundle . '.edit_form', $params);
            $url_string = $url->toString();
            $links[] = [
              'labelRaw' => $label_base . ' > ' . $this->t('Edit'),
              'value' => $url_string,
            ];
          }
          if ($this->moduleHandler->moduleExists('field_ui')) {
            if ($this->routeExists('entity.' . $content_entity . '.field_ui_fields')) {
              $url = Url::fromRoute('entity.' . $content_entity . '.field_ui_fields', $params);
              $url_string = $url->toString();
              $links[] = [
                'labelRaw' => $label_base . ' > ' . $this->t('Manage fields'),
                'value' => $url_string,
              ];
            }

            if ($this->routeExists('entity.entity_form_display.' . $content_entity . '.default')) {
              $url = Url::fromRoute('entity.entity_form_display.' . $content_entity . '.default', $params);
              $url_string = $url->toString();
              $links[] = [
                'labelRaw' => $label_base . ' > ' . $this->t('Manage form display'),
                'value' => $url_string,
              ];

            }
            if ($this->routeExists('entity.entity_view_display.' . $content_entity . '.default')) {
              $url = Url::fromRoute('entity.entity_view_display.' . $content_entity . '.default', $params);
              $url_string = $url->toString();
              $links[] = [
                'labelRaw' => $label_base . ' > ' . $this->t('Manage display'),
                'value' => $url_string,
              ];
            }
            if ($this->moduleHandler->moduleExists('devel') && $this->routeExists('entity.' . $content_entity_bundle . '.devel_load')) {
              $url = Url::fromRoute($route_name = 'entity.' . $content_entity_bundle . '.devel_load', $params);
              $url_string = $url->toString();
              $links[] = [
                'labelRaw' => $label_base . ' > ' . $this->t('Devel'),
                'value' => $url_string,
              ];
            }
            if ($this->routeExists('entity.' . $content_entity_bundle . '.delete_form')) {
              $url = Url::fromRoute('entity.' . $content_entity_bundle . '.delete_form', $params);
              $url_string = $url->toString();
              $links[] = [
                'labelRaw' => $label_base . ' > ' . $this->t('Delete'),
                'value' => $url_string,
              ];
            }
          }
        }
      }
    }

    // Add menu links.
    if ($this->moduleHandler->moduleExists('menu_ui')) {

      $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple();
      uasort($menus, [Menu::class, 'sort']);
      $menus = array_slice($menus, ExtraLinks::MAX_BUNDLE_NUMBER);

      $cache_tags = Cache::mergeTags($cache_tags, ['config:menu_list']);
      foreach ($menus as $menu_id => $menu) {
        $route_name = 'entity.menu.edit_form';
        $params = ['menu' => $menu_id];
        $url = Url::fromRoute($route_name, $params);
        $url_string = $url->toString();

        $links[] = [
          'labelRaw' => $this->t('Menus') . ' > ' . $menu->label(),
          'value' => $url_string,
        ];

        $route_name = 'entity.menu.add_link_form';
        $params = ['menu' => $menu_id];
        $url = Url::fromRoute($route_name, $params);
        $url_string = $url->toString();

        $links[] = [
          'labelRaw' => $this->t('Menus') . ' > ' . $menu->label() . ' > ' . $this->t('Add link'),
          'value' => $url_string,
        ];

        $menus = ['admin', 'devel', 'footer', 'main', 'tools', 'account'];
        if (!in_array($menu_id, $menus)) {

          $route_name = 'entity.menu.delete_form';
          $params = ['menu' => $menu_id];
          $url = Url::fromRoute($route_name, $params);
          $url_string = $url->toString();

          $links[] = [
            'labelRaw' => $this->t('Menus') . ' > ' . $menu->label() . ' > ' . $this->t('Delete'),
            'value' => $url_string,
          ];
        }
        if ($this->moduleHandler->moduleExists('devel') && $this->routeExists('entity.menu.devel_load')) {
          $route_name = 'entity.menu.devel_load';
          $params = ['menu' => $menu_id];
          $url = Url::fromRoute($route_name, $params);
          $url_string = $url->toString();

          $links[] = [
            'labelRaw' => $this->t('Menus') . ' > ' . $menu->label() . ' > ' . $this->t('Devel'),
            'value' => $url_string,
          ];
        }
      }
    }

    $this->toolbarCache->set($cid, $links, Cache::PERMANENT, $cache_tags);

    return $links;
  }

  /**
   * Get a list of content entities.
   *
   * @return array
   *   An array of metadata about content entities.
   */
  protected function getBundleableEntitiesList() {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $content_entities = [];
    foreach ($entity_types as $key => $entity_type) {
      if ($entity_type->getBundleEntityType() && ($entity_type->get('field_ui_base_route') != '')) {
        $content_entities[$key] = [
          'content_entity' => $key,
          'content_entity_bundle' => $entity_type->getBundleEntityType(),
        ];
      }
    }
    return $content_entities;
  }

  /**
   * Get an array of entity types that should trigger a menu rebuild.
   *
   * @return array
   *   An array of entity machine names.
   */
  public function getRebuildEntityTypes() {
    $types = ['menu'];
    $content_entities = $this->getBundleableEntitiesList();
    $types = array_merge($types, array_column($content_entities, 'content_entity_bundle'));
    return $types;
  }

  /**
   * Determine if a route exists by name.
   *
   * @param string $route_name
   *   The name of the route to check.
   *
   * @return bool
   *   Whether a route with that route name exists.
   */
  public function routeExists($route_name) {
    return (count($this->routeProvider->getRoutesByNames([$route_name])) === 1);
  }

}