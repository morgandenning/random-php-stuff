<?php

# Require Object Library
require_once dirname(__FILE__) . '/libs/objects/object.php';

function generateUUID() {
  return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
  mt_rand(0, 0xffff),
  mt_rand(0, 0xffff),
  mt_rand(0, 0xffff),
  mt_rand(0, 0x0fff) | 0x4000,
  mt_rand(0, 0x3fff) | 0x8000,
  mt_rand(0, 0xffff),
  mt_rand(0, 0xffff),
  mt_rand(0, 0xffff)
);
}

# Set Autoloader
\objects\object::registerAutoloader();


try {

  $oObject = \tests\test::new();

  var_dump($oObject);

  var_dump("UUID TEST");

  var_dump($oObject->uuid->set(generateUUID())->get());

  var_dump($oObject->uuid);
  var_dump($oObject->uuid->get());

  var_dump("TEXT TEST");

  var_dump($oObject->set(['text' => 'asd']));

  var_dump($oObject);

  var_dump("oObject->text");
  var_dump($oObject->text);

  var_dump("oObject->text->__toString");
  var_dump((string)$oObject->text);

  var_dump('oObject->text->get()');
  var_dump($oObject->text->get());

  var_dump($oObject->snapshot());

  var_dump('snapshot->__toString');
  $sSnapshot = (string)$oObject->snapshot();
  var_dump($sSnapshot);

  var_dump('snapshot:fromJson');

  var_dump('snapshot:decode');
  var_dump(json_decode((string)$sSnapshot, true));

  //var_dump('snapshot:fromJson');
  //\objects\snapshot::fromJson((string)$sSnapshot);

  var_dump('success');

} catch (\Exception $e) {
  var_dump("INDEX EXCEPTION");
  var_dump($e->getMessage());
}
