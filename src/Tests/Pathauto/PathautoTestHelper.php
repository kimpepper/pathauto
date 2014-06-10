<?php

/**
 * @file
 * Functionality tests for Pathauto.
 *
 * @ingroup pathauto
 */

namespace Drupal\pathauto\Tests\Pathauto;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Helper test class with some added functions for testing.
 */
class PathautoTestHelper extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('path', 'token', 'pathauto', 'taxonomy', 'views');

  public function assertToken($type, $object, $token, $expected) {
    $tokens = \Drupal::token()->generate($type, array($token => $token), array($type => $object));
    $tokens += array($token => '');
    $this->assertIdentical($tokens[$token], $expected, t("Token value for [@type:@token] was '@actual', expected value '@expected'.", array('@type' => $type, '@token' => $token, '@actual' => $tokens[$token], '@expected' => $expected)));
  }

  public function saveAlias($source, $alias, $language = Language::LANGCODE_NOT_SPECIFIED) {
    return \Drupal::service('path.alias_storage')->save($source, $alias, $language);
  }

  public function saveEntityAlias(EntityInterface $entity, $alias, $language = Language::LANGCODE_NOT_SPECIFIED) {
    return $this->saveAlias($entity->getSystemPath(), $alias, $language);
  }

  public function assertEntityAlias(EntityInterface $entity, $expected_alias, $language = Language::LANGCODE_NOT_SPECIFIED) {
    $this->assertAlias($entity->getSystemPath(), $expected_alias, $language);
  }

  public function assertEntityAliasExists(EntityInterface $entity) {
    return $this->assertAliasExists(array('source' => $entity->getSystemPath()));
  }

  public function assertNoEntityAlias(EntityInterface $entity, $language = Language::LANGCODE_NOT_SPECIFIED) {
    $this->assertEntityAlias($entity, $entity->getSystemPath(), $language);
  }

  public function assertNoEntityAliasExists(EntityInterface $entity) {
    $this->assertNoAliasExists(array('source' => $entity->getSystemPath()));
  }

  public function assertAlias($source, $expected_alias, $language = Language::LANGCODE_NOT_SPECIFIED) {
    $alias = array('alias' => $source);
    foreach (db_select('url_alias')->fields('url_alias')->condition('source', $source)->execute() as $row) {
      $alias = (array) $row;
      if ($row->alias == $expected_alias) {
        break;
      }
    }
    $this->assertIdentical($alias['alias'], $expected_alias, t("Alias for %source with language '@language' was %actual, expected %expected.",
      array('%source' => $source, '%actual' => $alias['alias'], '%expected' => $expected_alias, '@language' => $language)));
  }

  public function assertAliasExists($conditions) {
    $path = \Drupal::service('path.alias_storage')->load($conditions);
    $this->assertTrue($path, t('Alias with conditions @conditions found.', array('@conditions' => var_export($conditions, TRUE))));
    return $path;
  }

  public function assertNoAliasExists($conditions) {
    $alias = \Drupal::service('path.alias_storage')->load($conditions);
    $this->assertFalse($alias, t('Alias with conditions @conditions not found.', array('@conditions' => var_export($conditions, TRUE))));
  }

  public function deleteAllAliases() {
    db_delete('url_alias')->execute();
    \Drupal::service('path.alias_manager')->cacheClear();
  }

  /**
   * @param array $values
   * @return \Drupal\taxonomy\VocabularyInterface
   */
  public function addVocabulary(array $values = array()) {
    $name = drupal_strtolower($this->randomName(5));
    $values += array(
      'name' => $name,
      'vid' => $name,
    );
    $vocabulary = entity_create('taxonomy_vocabulary', $values);
    $vocabulary->save();

    return $vocabulary;
  }

  public function addTerm(VocabularyInterface $vocabulary, array $values = array()) {
    $values += array(
      'name' => drupal_strtolower($this->randomName(5)),
      'vid' => $vocabulary->id(),
    );

    $term = entity_create('taxonomy_term', $values);
    $term->save();
    return $term;
  }

  public function assertEntityPattern($entity_type, $bundle, $language = Language::LANGCODE_NOT_SPECIFIED, $expected) {
    drupal_static_reset('pathauto_pattern_load_by_entity');
    $this->refreshVariables();
    $pattern = pathauto_pattern_load_by_entity($entity_type, $bundle, $language);
    $this->assertIdentical($expected, $pattern);
  }

  public function drupalGetTermByName($name, $reset = FALSE) {
    if ($reset) {
      // @todo - implement cache reset.
    }
    $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadByProperties(array('name' => $name));
    return !empty($terms) ? reset($terms) : FALSE;
  }
}