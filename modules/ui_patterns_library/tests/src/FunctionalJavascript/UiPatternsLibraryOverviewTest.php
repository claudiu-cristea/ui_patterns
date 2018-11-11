<?php

namespace Drupal\Tests\ui_patterns_library\FunctionalJavascript;

use Drupal\Core\Serialization\Yaml;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test patterns overview page.
 *
 * @group ui_patterns_library
 */
class UiPatternsLibraryOverviewTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ui_patterns',
    'ui_patterns_library',
    'ui_patterns_library_module_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container->get('theme_installer')->install(['ui_patterns_library_theme_test']);
    $this->container->get('theme_handler')->setDefault('ui_patterns_library_theme_test');
    $this->container->set('theme.registry', NULL);
  }

  /**
   * Tests overview page.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testOverviewPage() {
    $session = $this->assertSession();

    $user = $this->drupalCreateUser(['access patterns page']);
    $this->drupalLogin($user);
    $this->drupalGet('/patterns');

    $session->elementContains('css', 'h1', 'Pattern library');
    $session->elementContains('css', 'h2', 'Available patterns');

    foreach ($this->getExpectedPatterns() as $index => $pattern) {

      // Assert pattern anchor link.
      $this->assertListLink($index + 1, $pattern['label'], $pattern['name']);

      // Assert pattern preview.
      $root = '.pattern-preview__' . $pattern['name'];
      $session->elementExists('css', $root);
      $session->elementContains('css', "$root > h3.pattern-preview__label", $pattern['label']);
      $session->elementContains('css', "$root > p.pattern-preview__description", $pattern['description']);

      // Assert metadata block.
      $this->assertPatternFields($root, $pattern);

      if (!$pattern['has_variants']) {
        // Make sure no variant markup exists.
        $session->elementNotExists('css', "$root > fieldset.pattern-preview__preview > .pattern-preview__variants");

        // Assert preview content when without variants.
        $session->elementContains('css', "$root > fieldset.pattern-preview__preview > .pattern-preview__markup", $pattern['preview']);
      }
      else {
        // Assert that variant markup exists.
        $session->elementExists('css', "$root > fieldset.pattern-preview__preview > .pattern-preview__variants");

        // Assert variant meta information and preview.
        foreach ($pattern['variants'] as $variant) {
          $this->assertPatternVariant($root, $variant);
        }
      }
    }
  }

  /**
   * Assert pattern table fields.
   *
   * @param string $root
   *   CSS selector of element containing the table.
   * @param array $pattern
   *   Pattern definition.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   */
  protected function assertPatternFields($root, array $pattern) {
    $session = $this->assertSession();

    // Assert table header.
    foreach (['Field', 'Label', 'Type', 'Description'] as $index => $item) {
      $child = $index + 1;
      $session->elementContains('css', "$root > table.pattern-preview__fields > thead > tr > th:nth-child($child)", $item);
    }

    // Assert field table rows.
    foreach ($pattern['fields'] as $index => $field) {
      $child = $index + 1;
      $row_root = "$root > table.pattern-preview__fields > tbody > tr:nth-child($child)";
      $session->elementContains('css', "$row_root > td:nth-child(1)", $field['name']);
      $session->elementContains('css', "$row_root > td:nth-child(2)", $field['label']);
      $session->elementContains('css', "$row_root > td:nth-child(3)", $field['type']);
      $session->elementContains('css', "$row_root > td:nth-child(4)", $field['description']);
    }
  }

  /**
   * Assert pattern variant metadata and preview.
   *
   * @param string $root
   *   CSS selector of element containing the table.
   * @param array $variant
   *   Variant expected values.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   */
  protected function assertPatternVariant($root, array $variant) {
    $session = $this->assertSession();
    $name = $variant['meta']['name'];

    // Assert table header.
    foreach (['Variant', 'Name', 'Description'] as $index => $item) {
      $child = $index + 1;
      $session->elementContains('css', "$root table.pattern-preview__variants--$name > thead > tr > th:nth-child($child)", $item);
    }

    // Assert variant meta table rows.
    $row_root = "$root table.pattern-preview__variants--$name > tbody > tr";
    $session->elementContains('css', "$row_root > td:nth-child(1)", $variant['meta']['name']);
    $session->elementContains('css', "$row_root > td:nth-child(2)", $variant['meta']['label']);
    $session->elementContains('css', "$row_root > td:nth-child(3)", $variant['meta']['description']);

    // Assert variant preview.
    $session->elementContains('css', "$root .pattern-preview__markup--variant_$name", $variant['preview']);
  }

  /**
   * Assert pattern overview list link.
   *
   * @param int $index
   *   Position on list.
   * @param string $label
   *   Pattern label.
   * @param string $name
   *   Pattern machine name.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   */
  protected function assertListLink($index, $label, $name) {
    $this->assertSession()->elementContains('css', "ul > li:nth-child($index) > a", $label);
    $this->assertSession()->elementAttributeContains('css', "ul > li:nth-child($index) > a", 'href', '#' . $name);
  }

  /**
   * Get expected patterns.
   */
  protected function getExpectedPatterns() {
    return Yaml::decode(file_get_contents(__DIR__ . '/../../fixtures/overview-page-patterns.yml'));
  }

}
