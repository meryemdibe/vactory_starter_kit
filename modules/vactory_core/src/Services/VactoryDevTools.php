<?php

namespace Drupal\vactory_core\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\field\Entity\FieldConfig;

/**
 * Service for vactory tools.
 */
class VactoryDevTools {

  /**
   * Field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Key value factory service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueFactory;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    EntityFieldManagerInterface $entityFieldManager,
    KeyValueFactoryInterface $keyValueFactory,
    LanguageManagerInterface $languageManager,
    FileSystemInterface $fileSystem,
    ConfigFactoryInterface $configFactory
  ) {
    $this->entityFieldManager = $entityFieldManager;
    $this->keyValueFactory = $keyValueFactory;
    $this->languageManager = $languageManager;
    $this->fileSystem = $fileSystem;
    $this->configFactory = $configFactory;
  }

  /**
   * Data encryption.
   */
  public function encrypt($data) {
    $key = '7O9KM8O44nO9cmZL';
    return bin2hex(openssl_encrypt($data, "aes-128-ecb", $key, OPENSSL_RAW_DATA));
  }

  /**
   * Data decryption.
   */
  public function decrypt($data) {
    $key = '7O9KM8O44nO9cmZL';
    return openssl_decrypt(pack("H*", $data), "aes-128-ecb", $key, OPENSSL_RAW_DATA);
  }

  /**
   * Clean entity config field from database.
   */
  public function cleanEntityConfigFields($entity_type, $bundle) {
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    $fields = array_keys(array_filter($fields, function ($field) {
      return $field instanceof FieldConfig;
    }));
    $field_map_kv_store = $this->keyValueFactory->get('entity.definitions.bundle_field_map');
    $map = $field_map_kv_store->get($entity_type);
    foreach ($fields as $field_name) {
      unset($map[$field_name]['bundles'][$bundle]);
    }
    $field_map_kv_store->set($entity_type, $map);
  }

  /**
   * Clean given module configs.
   */
  public function cleanModuleConfigs($module) {
    $languages = array_keys($this->languageManager->getLanguages());
    $install_details = [];
    $path_resolver = \Drupal::service('extension.path.resolver');
    foreach ($languages as $langcode) {
      $lang_install_dir = $path_resolver->getPath('module', $module) . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY . '/language/' . $langcode;
      if (file_exists($lang_install_dir)) {
        $lang_install_details =  $this->fileSystem->scanDirectory($lang_install_dir, "/\.(yml)$/");
        $install_details = array_merge($install_details, $lang_install_details);
      }
    }
    $install_dir = $path_resolver->getPath('module', $module) . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
    if (file_exists($install_dir)) {
      $install_details =  $this->fileSystem->scanDirectory($install_dir, "/\.(yml)$/");
    }

    if (!empty($install_details)) {
      foreach ($install_details as $config_value) {
        if (strpos($config_value->name, 'entityqueue.entity_queue') === 0) {
          $queue_name = explode('.', $config_value->name)[2];
          $queue = EntitySubqueue::load($queue_name);
          if ($queue) {
            $queue->delete();
          }
        }
        $this->configFactory->getEditable($config_value->name)->delete();
      }
    }
  }

  /**
   * Replace accents from given string.
   */
  public function unaccent(string $string, $space_replacement_char = NULL) {
    $str = str_replace(array_keys(static::TRANSLATERATION),
      array_values(static::TRANSLATERATION),
      $string);
    if (!empty($space_replacement_char) && is_string($space_replacement_char)) {
      $str = str_replace(' ', $space_replacement_char, $str);
    }
    return $str;
  }

  // phpcs:disable
  const TRANSLATERATION = [
    'Ĳ' => 'I', 'Ö' => 'O','Œ' => 'O','Ü' => 'U','ä' => 'a','æ' => 'a',
    'ĳ' => 'i','ö' => 'o','œ' => 'o','ü' => 'u','ß' => 's','ſ' => 's',
    'À' => 'A','Á' => 'A','Â' => 'A','Ã' => 'A','Ä' => 'A','Å' => 'A',
    'Æ' => 'A','Ā' => 'A','Ą' => 'A','Ă' => 'A','Ç' => 'C','Ć' => 'C',
    'Č' => 'C','Ĉ' => 'C','Ċ' => 'C','Ď' => 'D','Đ' => 'D','È' => 'E',
    'É' => 'E','Ê' => 'E','Ë' => 'E','Ē' => 'E','Ę' => 'E','Ě' => 'E',
    'Ĕ' => 'E','Ė' => 'E','Ĝ' => 'G','Ğ' => 'G','Ġ' => 'G','Ģ' => 'G',
    'Ĥ' => 'H','Ħ' => 'H','Ì' => 'I','Í' => 'I','Î' => 'I','Ï' => 'I',
    'Ī' => 'I','Ĩ' => 'I','Ĭ' => 'I','Į' => 'I','İ' => 'I','Ĵ' => 'J',
    'Ķ' => 'K','Ľ' => 'K','Ĺ' => 'K','Ļ' => 'K','Ŀ' => 'K','Ł' => 'L',
    'Ñ' => 'N','Ń' => 'N','Ň' => 'N','Ņ' => 'N','Ŋ' => 'N','Ò' => 'O',
    'Ó' => 'O','Ô' => 'O','Õ' => 'O','Ø' => 'O','Ō' => 'O','Ő' => 'O',
    'Ŏ' => 'O','Ŕ' => 'R','Ř' => 'R','Ŗ' => 'R','Ś' => 'S','Ş' => 'S',
    'Ŝ' => 'S','Ș' => 'S','Š' => 'S','Ť' => 'T','Ţ' => 'T','Ŧ' => 'T',
    'Ț' => 'T','Ù' => 'U','Ú' => 'U','Û' => 'U','Ū' => 'U','Ů' => 'U',
    'Ű' => 'U','Ŭ' => 'U','Ũ' => 'U','Ų' => 'U','Ŵ' => 'W','Ŷ' => 'Y',
    'Ÿ' => 'Y','Ý' => 'Y','Ź' => 'Z','Ż' => 'Z','Ž' => 'Z','à' => 'a',
    'á' => 'a','â' => 'a','ã' => 'a','ā' => 'a','ą' => 'a','ă' => 'a',
    'å' => 'a','ç' => 'c','ć' => 'c','č' => 'c','ĉ' => 'c','ċ' => 'c',
    'ď' => 'd','đ' => 'd','è' => 'e','é' => 'e','ê' => 'e','ë' => 'e',
    'ē' => 'e','ę' => 'e','ě' => 'e','ĕ' => 'e','ė' => 'e','ƒ' => 'f',
    'ĝ' => 'g','ğ' => 'g','ġ' => 'g','ģ' => 'g','ĥ' => 'h','ħ' => 'h',
    'ì' => 'i','í' => 'i','î' => 'i','ï' => 'i','ī' => 'i','ĩ' => 'i',
    'ĭ' => 'i','į' => 'i','ı' => 'i','ĵ' => 'j','ķ' => 'k','ĸ' => 'k',
    'ł' => 'l','ľ' => 'l','ĺ' => 'l','ļ' => 'l','ŀ' => 'l','ñ' => 'n',
    'ń' => 'n','ň' => 'n','ņ' => 'n','ŉ' => 'n','ŋ' => 'n','ò' => 'o',
    'ó' => 'o','ô' => 'o','õ' => 'o','ø' => 'o','ō' => 'o','ő' => 'o',
    'ŏ' => 'o','ŕ' => 'r','ř' => 'r','ŗ' => 'r','ś' => 's','š' => 's',
    'ť' => 't','ù' => 'u','ú' => 'u','û' => 'u','ū' => 'u','ů' => 'u',
    'ű' => 'u','ŭ' => 'u','ũ' => 'u','ų' => 'u','ŵ' => 'w','ÿ' => 'y',
    'ý' => 'y','ŷ' => 'y','ż' => 'z','ź' => 'z','ž' => 'z','Α' => 'A',
    'Ά' => 'A','Ἀ' => 'A','Ἁ' => 'A','Ἂ' => 'A','Ἃ' => 'A','Ἄ' => 'A',
    'Ἅ' => 'A','Ἆ' => 'A','Ἇ' => 'A','ᾈ' => 'A','ᾉ' => 'A','ᾊ' => 'A',
    'ᾋ' => 'A','ᾌ' => 'A','ᾍ' => 'A','ᾎ' => 'A','ᾏ' => 'A','Ᾰ' => 'A',
    'Ᾱ' => 'A','Ὰ' => 'A','ᾼ' => 'A','Β' => 'B','Γ' => 'G','Δ' => 'D',
    'Ε' => 'E','Έ' => 'E','Ἐ' => 'E','Ἑ' => 'E','Ἒ' => 'E','Ἓ' => 'E',
    'Ἔ' => 'E','Ἕ' => 'E','Ὲ' => 'E','Ζ' => 'Z','Η' => 'I','Ή' => 'I',
    'Ἠ' => 'I','Ἡ' => 'I','Ἢ' => 'I','Ἣ' => 'I','Ἤ' => 'I','Ἥ' => 'I',
    'Ἦ' => 'I','Ἧ' => 'I','ᾘ' => 'I','ᾙ' => 'I','ᾚ' => 'I','ᾛ' => 'I',
    'ᾜ' => 'I','ᾝ' => 'I','ᾞ' => 'I','ᾟ' => 'I','Ὴ' => 'I','ῌ' => 'I',
    'Θ' => 'T','Ι' => 'I','Ί' => 'I','Ϊ' => 'I','Ἰ' => 'I','Ἱ' => 'I',
    'Ἲ' => 'I','Ἳ' => 'I','Ἴ' => 'I','Ἵ' => 'I','Ἶ' => 'I','Ἷ' => 'I',
    'Ῐ' => 'I','Ῑ' => 'I','Ὶ' => 'I','Κ' => 'K','Λ' => 'L','Μ' => 'M',
    'Ν' => 'N','Ξ' => 'K','Ο' => 'O','Ό' => 'O','Ὀ' => 'O','Ὁ' => 'O',
    'Ὂ' => 'O','Ὃ' => 'O','Ὄ' => 'O','Ὅ' => 'O','Ὸ' => 'O','Π' => 'P',
    'Ρ' => 'R','Ῥ' => 'R','Σ' => 'S','Τ' => 'T','Υ' => 'Y','Ύ' => 'Y',
    'Ϋ' => 'Y','Ὑ' => 'Y','Ὓ' => 'Y','Ὕ' => 'Y','Ὗ' => 'Y','Ῠ' => 'Y',
    'Ῡ' => 'Y','Ὺ' => 'Y','Φ' => 'F','Χ' => 'X','Ψ' => 'P','Ω' => 'O',
    'Ώ' => 'O','Ὠ' => 'O','Ὡ' => 'O','Ὢ' => 'O','Ὣ' => 'O','Ὤ' => 'O',
    'Ὥ' => 'O','Ὦ' => 'O','Ὧ' => 'O','ᾨ' => 'O','ᾩ' => 'O','ᾪ' => 'O',
    'ᾫ' => 'O','ᾬ' => 'O','ᾭ' => 'O','ᾮ' => 'O','ᾯ' => 'O','Ὼ' => 'O',
    'ῼ' => 'O','α' => 'a','ά' => 'a','ἀ' => 'a','ἁ' => 'a','ἂ' => 'a',
    'ἃ' => 'a','ἄ' => 'a','ἅ' => 'a','ἆ' => 'a','ἇ' => 'a','ᾀ' => 'a',
    'ᾁ' => 'a','ᾂ' => 'a','ᾃ' => 'a','ᾄ' => 'a','ᾅ' => 'a','ᾆ' => 'a',
    'ᾇ' => 'a','ὰ' => 'a','ᾰ' => 'a','ᾱ' => 'a','ᾲ' => 'a','ᾳ' => 'a',
    'ᾴ' => 'a','ᾶ' => 'a','ᾷ' => 'a','β' => 'b','γ' => 'g','δ' => 'd',
    'ε' => 'e','έ' => 'e','ἐ' => 'e','ἑ' => 'e','ἒ' => 'e','ἓ' => 'e',
    'ἔ' => 'e','ἕ' => 'e','ὲ' => 'e','ζ' => 'z','η' => 'i','ή' => 'i',
    'ἠ' => 'i','ἡ' => 'i','ἢ' => 'i','ἣ' => 'i','ἤ' => 'i','ἥ' => 'i',
    'ἦ' => 'i','ἧ' => 'i','ᾐ' => 'i','ᾑ' => 'i','ᾒ' => 'i','ᾓ' => 'i',
    'ᾔ' => 'i','ᾕ' => 'i','ᾖ' => 'i','ᾗ' => 'i','ὴ' => 'i','ῂ' => 'i',
    'ῃ' => 'i','ῄ' => 'i','ῆ' => 'i','ῇ' => 'i','θ' => 't','ι' => 'i',
    'ί' => 'i','ϊ' => 'i','ΐ' => 'i','ἰ' => 'i','ἱ' => 'i','ἲ' => 'i',
    'ἳ' => 'i','ἴ' => 'i','ἵ' => 'i','ἶ' => 'i','ἷ' => 'i','ὶ' => 'i',
    'ῐ' => 'i','ῑ' => 'i','ῒ' => 'i','ῖ' => 'i','ῗ' => 'i','κ' => 'k',
    'λ' => 'l','μ' => 'm','ν' => 'n','ξ' => 'k','ο' => 'o','ό' => 'o',
    'ὀ' => 'o','ὁ' => 'o','ὂ' => 'o','ὃ' => 'o','ὄ' => 'o','ὅ' => 'o',
    'ὸ' => 'o','π' => 'p','ρ' => 'r','ῤ' => 'r','ῥ' => 'r','σ' => 's',
    'ς' => 's','τ' => 't','υ' => 'y','ύ' => 'y','ϋ' => 'y','ΰ' => 'y',
    'ὐ' => 'y','ὑ' => 'y','ὒ' => 'y','ὓ' => 'y','ὔ' => 'y','ὕ' => 'y',
    'ὖ' => 'y','ὗ' => 'y','ὺ' => 'y','ῠ' => 'y','ῡ' => 'y','ῢ' => 'y',
    'ῦ' => 'y','ῧ' => 'y','φ' => 'f','χ' => 'x','ψ' => 'p','ω' => 'o',
    'ώ' => 'o','ὠ' => 'o','ὡ' => 'o','ὢ' => 'o','ὣ' => 'o','ὤ' => 'o',
    'ὥ' => 'o','ὦ' => 'o','ὧ' => 'o','ᾠ' => 'o','ᾡ' => 'o','ᾢ' => 'o',
    'ᾣ' => 'o','ᾤ' => 'o','ᾥ' => 'o','ᾦ' => 'o','ᾧ' => 'o','ὼ' => 'o',
    'ῲ' => 'o','ῳ' => 'o','ῴ' => 'o','ῶ' => 'o','ῷ' => 'o','А' => 'A',
    'Б' => 'B','В' => 'V','Г' => 'G','Д' => 'D','Е' => 'E','Ё' => 'E',
    'Ж' => 'Z','З' => 'Z','И' => 'I','Й' => 'I','К' => 'K','Л' => 'L',
    'М' => 'M','Н' => 'N','О' => 'O','П' => 'P','Р' => 'R','С' => 'S',
    'Т' => 'T','У' => 'U','Ф' => 'F','Х' => 'K','Ц' => 'T','Ч' => 'C',
    'Ш' => 'S','Щ' => 'S','Ы' => 'Y','Э' => 'E','Ю' => 'Y','Я' => 'Y',
    'а' => 'A','б' => 'B','в' => 'V','г' => 'G','д' => 'D','е' => 'E',
    'ё' => 'E','ж' => 'Z','з' => 'Z','и' => 'I','й' => 'I','к' => 'K',
    'л' => 'L','м' => 'M','н' => 'N','о' => 'O','п' => 'P','р' => 'R',
    'с' => 'S','т' => 'T','у' => 'U','ф' => 'F','х' => 'K','ц' => 'T',
    'ч' => 'C','ш' => 'S','щ' => 'S','ы' => 'Y','э' => 'E','ю' => 'Y',
    'я' => 'Y','ð' => 'd','Ð' => 'D','þ' => 't','Þ' => 'T','ა' => 'a',
    'ბ' => 'b','გ' => 'g','დ' => 'd','ე' => 'e','ვ' => 'v','ზ' => 'z',
    'თ' => 't','ი' => 'i','კ' => 'k','ლ' => 'l','მ' => 'm','ნ' => 'n',
    'ო' => 'o','პ' => 'p','ჟ' => 'z','რ' => 'r','ს' => 's','ტ' => 't',
    'უ' => 'u','ფ' => 'p','ქ' => 'k','ღ' => 'g','ყ' => 'q','შ' => 's',
    'ჩ' => 'c','ც' => 't','ძ' => 'd','წ' => 't','ჭ' => 'c','ხ' => 'k',
    'ჯ' => 'j','ჰ' => 'h'
  ];
  // phpcs:enable

}
