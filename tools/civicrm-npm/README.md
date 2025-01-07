## Idea

* Use npm to identify packages, updates, security issues, etc.
* This is a separate subfolder (`tools/civicrm-npm/`). It's a dev-tool and not a part of the regular release-requirements.
* Decisions from `tools/civicrm-npm/package-lock.json` are copied over to `composer.json` (`downloads`).

## Example Usage

```bash
cd tools/civicrm-npm/
npm i lodash@5.6.7     ## Download packages with npm
php export.php         ## Export concrete list to composer.json
```

## Comments

* An even slicker variation of this would be to teach `composer-downloads-plugin` how to
  directly read `tools/civicrm-npm/package-lock.json`.
* But first we need to figure out if we can persaude `npm` to download the needful packages.
* Issues:
    * Couldn't figure out how to get npm to download some of the packages. (See `package.json-todo`)
    * For some packages, `npm` is picking different zip files with different contents/layouts.
