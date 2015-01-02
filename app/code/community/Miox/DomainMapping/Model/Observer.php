<?php
class Miox_DomainMapping_Model_Observer
{
  static protected $_initialized = false;

  static protected $_domainMappings = array();
  static protected $_domainMappingsInfo = array();  
  static protected $_domainMappingInfo = array();  

  static protected $_urlReplacements = array();

  static protected $domain0 = null;
  static protected $domain1 = null;
  static protected $storeCode = null;


  public function resourceGetTablename($observer)
  {    
    if (self::$_initialized) return false;

    $config = Mage::getConfig();
    $configDomainMappings = $config->getNode('global/domainmapping');
    if (!$configDomainMappings) return false;

    foreach($configDomainMappings->asArray() as $configDomainMapping) {
      self::$_domainMappings[$configDomainMapping['from']] = $configDomainMapping['to'];
      self::$_domainMappingsInfo[$configDomainMapping['from']] = $configDomainMapping;
    }
    self::$_initialized = true;

    $domain0 = null;
    if (array_key_exists('HTTP_HOST', $_SERVER)) $domain0 = $_SERVER['HTTP_HOST'];
    if (!$domain0 && array_key_exists('HTTP_X_HOST', $_SERVER)) $domain0 = $_SERVER['HTTP_X_HOST'];
    if (!$domain0) return false;

    if (!array_key_exists($domain0, self::$_domainMappings)) return false;
    self::$_domainMappingInfo = self::$_domainMappingsInfo[$domain0];    

    $domain1 = self::$_domainMappings[$domain0];
    self::$domain0 = $_SERVER['HTTP_HOST0'] = $domain0;

    if (stripos($domain1, 'store:') === 0) {
      self::$storeCode = $storeCode = substr($domain1, 6);      
      $domain1 = $domain0;      
    } else {
      self::$domain1 = $_SERVER['HTTP_HOST'] = $domain1;
      self::$_urlReplacements[$domain1] = $domain0;
    }
    
  }

  public function controllerFrontInitBefore($observer)
  {
    if (self::$storeCode) {
      $storeId = Mage::app()->getStore(self::$storeCode)->getId();
      if ($storeId) {
        Mage::app()->setCurrentStore($storeId);
      }
    }

    if (array_key_exists('design', self::$_domainMappingInfo)) {
      foreach(self::$_domainMappingInfo['design'] as $key => $value) {
        switch($key) {
          case 'package':
            Mage::getDesign()->setPackageName($value);
            break;
          case 'theme':
            Mage::getDesign()->setTheme($value);
            break;
          case 'skin':
            Mage::getDesign()->setTheme('skin', $value);
            break;
        }
      }
    }
    
    if (empty(self::$_urlReplacements)) return false;

    $store = Mage::app()->getStore();
    $code  = $store->getCode();
    $request = $observer->getFront()->getRequest();
    
    $configPaths = array(
      'web/unsecure/base_url',
      'web/unsecure/base_link_url',
      'web/unsecure/base_skin_url',
      'web/unsecure/base_media_url',

      'web/secure/base_url',
      'web/secure/base_link_url',
      'web/secure/base_skin_url',
      'web/secure/base_media_url',
    );

    foreach($configPaths as $configPath) {
      $value0 = $store->getConfig($configPath);
      foreach(self::$_urlReplacements as $search => $replace) {
        $value1 = str_replace("//$search/", "//$replace/", $value0);
      }
      $store->setConfig($configPath, $value1);
    }

  }

  public function coreBlockAbstractToHtmlBefore($observer)
  {
    if (empty(self::$_urlReplacements)) return false;

    $block  = $observer->getBlock();
    $cacheKeyInfo = $block->getCacheKeyInfo();
    if (array_key_exists(1, $cacheKeyInfo)) {
      $cacheKeyInfo[1] .= '_'.self::$domain0;
    }
    $cacheKey = array_values($cacheKeyInfo);
    $cacheKey = implode('|', $cacheKey);
    $cacheKey = sha1($cacheKey);
    $block->setCacheKey($cacheKey);
  }
}