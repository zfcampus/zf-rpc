# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.3.1 - 2016-10-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#11](https://github.com/zfcampus/zf-rpc/pull/11) fixes a typo in an import
  statement.
- [#12](https://github.com/zfcampus/zf-rpc/pull/12) fixes registration of the
  `ViewJsonFactory`, ensuring it registers at its original priority of 100.

## 1.3.0 - 2016-07-12

### Added

- [#10](https://github.com/zfcampus/zf-rpc/pull/10) adds support for v3 releases
  of Zend Framework components, while retaining compatibility with v2 releases.
- [#10](https://github.com/zfcampus/zf-rpc/pull/10) adds
  `ZF\Rpc\Factory\OptionsListenerFactory`, which was extracted from the `Module`
  class.
- [#10](https://github.com/zfcampus/zf-rpc/pull/10) exposes the module to
  zend-component-installer.

### Deprecated

- Nothing.

### Removed

- [#10](https://github.com/zfcampus/zf-rpc/pull/10) removes support for PHP 5.5.

### Fixed

- Nothing.
