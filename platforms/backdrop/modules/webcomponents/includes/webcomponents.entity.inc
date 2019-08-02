<?php


/**
 * WebComponentEntity extends Entity.
 *
 * A subclass of Entity is a requirement for creating a custom entity. It is
 * also a minimum requirement to define the methods included here, even as
 * empty functions.
 */
class WebComponentEntity extends Entity {

  /**
   * Implements EntityInterface::id().
   */
  public function id() {}

  /**
   * Implements EntityInterface::entityType().
   */
  public function entityType() {
    return 'webcomponent';
  }

  /**
   * Implements EntityInterface::bundle().
   */
  public function bundle() {}

  /**
   * Implements EntityInterface::label().
   */
  public function label() {}

  /**
   * Implements EntityInterface::uri().
   */
  public function uri() {}
}

/**
 * WebComponentEntityControllerInterface definition.
 *
 * We create an interface here because anyone could come along and
 * use hook_entity_info_alter() to change our controller class.
 * We want to let them know what methods our class needs in order
 * to function with the rest of the module, so here's a handy list.
 *
 * @see hook_entity_info_alter()
 */
interface WebcomponentEntityControllerInterface
  extends EntityControllerInterface {

  /**
   * Create an entity.
   */
  public function create($values);

  /**
   * Save an entity.
   *
   * @param object $entity
   *   The entity to save.
   */
  public function save($entity);

  /**
   * Delete an entity.
   *
   * @param object $entity
   *   The entity to delete.
   */
  public function delete($entity);

}

/**
 * WebComponentEntityController extends BackdropDefaultEntityController.
 *
 * Our subclass of BackdropDefaultEntityController lets us add a few
 * important create, update, and delete methods.
 */
class WebComponentEntityController
  extends DefaultEntityController
  implements WebComponentEntityControllerInterface {

  /**
   * Create and return a new webcomponent entity.
   */
  public function create($values) {
    // It is important when creating entities in Backdrop to ensure that you
    // create an instance of the core Entity class rather than an instance of
    // stdClass(). This would lead to exceptions on attempting to save your
    // custom entity.
    // See https://github.com/backdrop/backdrop-issues/issues/2558
    $entity = new WebComponentEntity();
    $entity->wid = NULL;
    $entity->type = $values['type'];
    $entity->title = $values['title'];
    $entity->file = $values['file'];
    $entity->description = $values['description'];
    $entity->machine_name = $values['machine_name'];
    $entity->properties = $values['properties'];
    return $entity;
  }

  /**
   * Saves the custom fields using backdrop_write_record().
   */
  public function save($entity) {
    // Invoke hook_entity_presave().
    module_invoke_all('entity_presave', $entity, 'webcomponent');
    // The 'primary_keys' argument determines whether this will be an insert
    // or an update. So if the entity already has an ID, we'll specify
    // wids as the key.
    $primary_keys = $entity->wid ? 'wid' : array();
    // Write out the entity record.
    backdrop_write_record('webcomponents', $entity, $primary_keys);
    // We're going to invoke either hook_entity_update() or
    // hook_entity_insert(), depending on whether or not this is a
    // new entity. We'll just store the name of hook_entity_insert()
    // and change it if we need to.
    $invocation = 'entity_insert';
    // Now we need to either insert or update the fields which are
    // attached to this entity. We use the same primary_keys logic
    // to determine whether to update or insert, and which hook we
    // need to invoke.
    if (empty($primary_keys)) {
      field_attach_insert('webcomponent', $entity);
    }
    else {
      field_attach_update('webcomponent', $entity);
      $invocation = 'entity_update';
    }
    // Invoke either hook_entity_update() or hook_entity_insert().
    module_invoke_all($invocation, $entity, 'webcomponent');
    return $entity;
  }

  /**
   * Delete a single entity.
   *
   * Really a convenience function for deleteMultiple().
   */
  public function delete($entity) {
    $this->deleteMultiple(array($entity));
  }

  /**
   * Delete one or more webcomponent entities.
   *
   * Deletion is unfortunately not supported in the base
   * BackdropDefaultEntityController class.
   *
   * @param array $entities
   *   An array of entity IDs or a single numeric ID.
   */
  public function deleteMultiple($entities) {
    $wids = array();
    if (!empty($entities)) {
      $transaction = db_transaction();
      try {
        foreach ($entities as $entity) {
          // Invoke hook_entity_delete().
          module_invoke_all('entity_delete', $entity, 'webcomponent');
          field_attach_delete('webcomponent', $entity);
          $wids[] = $entity->wid;
        }
        db_delete('webcomponents')
          ->condition('wid', $wids, 'IN')
          ->execute();
      }
      catch (Exception $e) {
        $transaction->rollback();
        watchdog_exception('webcomponent', $e);
        throw $e;
      }
    }
  }
}

