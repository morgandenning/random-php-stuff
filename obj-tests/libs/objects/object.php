<?php

namespace objects {

  class object {
    static public $sTable;

    static public $aValues = [];

    /* Static Functions */
    static public function registerAutoloader() {
      spl_autoload_register(function($xClass) {
        var_dump("object::registerAutoloader");
        var_dump($xClass);
      });
    }

    static public function getFieldObject($sField = null) {
      var_dump("object:getFieldObject");
      try {
        var_dump($sField);

        return (new $sField());

      } catch (\Exception $e){
        var_dump("getFieldObject EXCEPTION");
        throw new \Exception("getFieldObject Failed");
      }
    }

    static public function new(...$aArgs): object {
      return new static;
    }

    public function __set($sKey, $sVal) {

      var_dump("__SET");

      var_dump($sKey);
      var_dump($sVal);

      $this->{$sKey} = $sVal;
    }
    public function __get($sKey) {

      var_dump("__GET");

      try {

        if (isset(static::$aFields[$sKey])) {
          if (!isset($this->{$sKey})) {
            var_dump("CREATE FIELD OBJECT");
            $this->{$sKey} = self::getFieldObject(static::$aFields[$sKey]);
          }

          var_dump($this->{$sKey});

          return $this->{$sKey};
        } else {
          throw new \objects\exceptions\InvalidField("Invalid Field: {$sKey}");
        }
      } catch (\objects\exceptions\InvalidField $e) {
        var_dump("__GET EXCEPTION");
        var_dump($e->getMessage());
        throw new \Exception("__GET FAILED");
      }

    }
    // public function __isset($sKey) {
    // 	//return (isset($this->aValues[$sKey]) || isset($this->{$sKey}));
    // }

    public function set(...$aArgs): object {
      var_dump("object:set");

      if (isset($aArgs, $aArgs[0]) && is_array($aArgs[0])) {
        // Multiple Sets
        foreach ($aArgs[0] as $xKey => $xVal) {
          $this->{$xKey}->set($xVal);
        }
      } else if (isset($aArgs, $aArgs[0], $aArgs[1])) {
        $this->{$aArgs[0]}->set($aArgs[1]);
      }


      return $this;
    }

    public function snapshot(...$aArgs): object {
      var_dump("object:snapshot");
      //var_dump($this);

      return new \objects\snapshot($this);

      return true;
    }

  }

  class snapshot extends object {
    public function __construct(...$aArgs) {
      var_dump('snapshot:__construct');

      if (count($aArgs > 0)) {
        var_dump($aArgs);
        var_dump(get_class($aArgs[0]));

        $this->{get_class($aArgs[0])} = $aArgs[0];
      }
    }

    public function __toString() {
      return json_encode($this, JSON_FORCE_OBJECT);
    }

    public function jsonSerialize() {
      $aData = [];
      foreach ($this as $k => $v) {
        var_dump($k);
        var_dump($v);
        $aData[$k] = $v;
      }
      return $aData;
    }


    static public function fromJson($sSnapshot = null) {
      if (is_null($sSnapshot)) {
        throw new \Exception('invalid snapshot');
      }

      $oSnapshot = null;
      $aSnapshot = json_decode($sSnapshot, true);
      $fGetAllObjects = function ($xData, $sKey) use (&$oSnapshot) {
        $fGetObject = function($xData, $sKey) use (&$oSnapshot) {
          var_dump('fGetObject');

          var_dump('$xData');
          var_dump($xData);

          var_dump('sKey');
          var_dump($sKey);

          $oSnapshot->{$sKey} = new $xData['id'];
        };

        var_dump('sKey');
        var_dump($sKey);

        var_dump('xData');
        var_dump($xData);

        try {
          $oSnapshot = new $sKey();

          array_walk($xData, $fGetObject);
        } catch (\Exception $e) {
          var_dump($e->getMessage());
        }
      };

      array_walk($aSnapshot, $fGetAllObjects);

      var_dump('post-array-walk $aSnapshot');
      var_dump($aSnapshot);

      var_dump('oSnapshot');
      var_dump($oSnapshot);

    }
  }

  abstract class field implements \JsonSerializable {

    protected $id;
    protected $value;

    public function __construct($sId = null) {
      var_dump("field::__construct");

      $aClassNamespace = explode('\\', static::class);

      $this->id = $sId ?? end($aClassNamespace);
    }

    public function __set($id, $val) {
      $this->{$id} = $val;
    }

    public function __get($id) {
      var_dump('field\get');
    }

    public function __toString() {
      return $this->value;
    }

    public function jsonSerialize($aData = []) {
      foreach ($this as $k => $v) {
        var_dump($k);
        var_dump($v);
        $aData[$k] = $v;
      }
      return $aData;
    }

    public function get() {
      var_dump("FIELD:GET");
      return $this->value;
    }

    public function set($xVal) {

      var_dump('field:set');

      try {
        $this->value = $this->validate($xVal);
        return $this;
      } catch (\objects\exceptions\ValidateFieldFailed $e) {
        var_dump('\objects\exceptions\ValidateFieldFailed');

        var_dump($e->getMessage());

        throw new \Exception("Failed Validation. No Object Created");

      }
    }

    abstract function validate($xVal);
  }

}

namespace objects\exceptions {
  abstract class Exception extends \Exception {
    protected $aErrors = null;

    public function __construct($sMessage, $sCode = 0, \Exception $oPrev = null, $aErrors = null) {
      parent::__construct($sMessage, $sCode, $oPrev);

      $this->aErrors = $aErrors;
    }

    public function getErrors() {
      return $this->aErrors;
    }
  }

  class ValidateFieldFailed extends Exception {}
    class InvalidField extends Exception {}
    }


    namespace objects\fields {

      class basic extends \objects\field {
        public function validate($xVal) {
          return $xVal;
        }
      }

      class uuid extends \objects\field {

        public function validate($xVal = false): string {
          if (preg_match("/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/i", $xVal)) {
            return $xVal;
          } else {
            throw new \objects\exceptions\ValidateFieldFailed("Failed Validation On UUID:{$xVal}");
          }
        }

      }

    }


    namespace tests {

      class object extends \objects\object {
        //
      }

      class test extends object {
        static public $sTable = '_table';

        static public $aFields = [
          'uuid' => '\objects\fields\uuid',
          'text' => '\objects\fields\basic'
        ];
      }

    }
