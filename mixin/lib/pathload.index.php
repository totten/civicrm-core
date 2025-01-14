<?php
namespace Civi\PathLoadSetup;

// Pathload folder would usually be registered by saying something like:
//
//   pathload()->addSearchDir(__DIR__ . '/lib');
//
// However, that would detect version#'s from the filenames -- which is a convenient
// practice for downstreams using backports. However, here, we are canonical.
// It would be quite inconvenient to rename the folder every time there's an edit.
//
// `addSearchItem()` allows us to register version-number programmatically -- so we don't have
// to manually set number.

$version6 = \CRM_Utils_System::version() . '.1'; /* Higher priority than contrib copies of same version... */
$version5 = preg_replace_callback(';^6\.(\d+)\.;', function ($m) {
  /* civimix-schema@5.83=>6.0 was purely superficial (to match core#)s. Continue 5.x option for compat. */
  return '5.' . (83 + $m[1]) . '.';
}, $version6);

// Register civimix-schema@5.x
\pathload()->addSearchItem('civimix-schema@5', $version5, __DIR__ . '/civimix-schema');
