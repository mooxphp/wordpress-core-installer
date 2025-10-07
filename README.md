# WordPress Core Installer

[![Tests](https://github.com/mooxphp/wordpress-core-installer/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/mooxphp/wordpress-core-installer/actions/workflows/tests.yml)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
[![Packagist](https://img.shields.io/packagist/dt/moox/wordpress-core-installer.svg)](https://packagist.org/packages/roots/wordpress-core-installer)
![GitHub tag](https://img.shields.io/github/tag/mooxphp/wordpress-core-installer.svg)

*This is a fork of [roots/wordpress-core-installer](https://github.com/roots/wordpress-core-installer) with some fixes to enhance compatibility with [moox/press](https://packagist.org/packages/moox/press)*

A custom Composer plugin to install WordPress core outside of `vendor`.

This installer is meant to support a rather specific WordPress installation setup in which WordPress is installed in a subdirectory ([see the WordPress codex on that subject](https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory)) and in which the location of `WP_CONTENT_DIR` and `WP_CONTENT_URL` have been customized to be outside of WordPress core ([see the WordPress codex on that subject](https://codex.wordpress.org/Editing_wp-config.php#Moving_wp-content_folder)). This is because composer will delete your whole wp-content directory every time it updates core if you don't separate the two. If that installation setup isn't what you are looking for, then this installer is probably not something you will want to use.

For more information on this site setup and using Composer to manage a whole WordPress site, [check out @Rarst's informational website](https://composer.rarst.net/) which also includes [a site stack example using this package](https://composer.rarst.net/recipe/site-stack/).

### Usage
To set up a custom WordPress build package to use this as a custom installer, add the following to your package's composer file:

```json
"type": "wordpress-core",
"require": {
	"moox/wordpress-core-installer": "^3.0"
}
```

By default, this package will install a `wordpress-core` type package in the `wordpress` directory. To change this you can add the following to either your custom WordPress core type package or the root composer package:

```json
"extra": {
	"wordpress-install-dir": "public/wp"
}
```

This works not only in the project's composer.json but also in a package's composer.json, the reason for the fork, and we default to `public/wp`.

The root composer package can also declare custom paths as an object keyed by package name:

```json
"extra": {
	"wordpress-install-dir": {
		"wordpress/wordpress": "wordpress",
		"roots/wordpress-core": "roots-wordpress"
	}
}
```

### License
This is licensed under the GPL version 2 or later.
