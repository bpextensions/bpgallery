# BP Gallery
A Joomla! 3 Gallery component.

## Requirements
- PHP 7.2
- Joomla! 3.9.x

## Features
- A drag & drop upload process.
- Multiple layouts available.
- Easy management.
- Following Joomla! best practices.
- Custom fields support.
- Multilingual support.

## Development
Development environment requires php 7.2+, node, composer and optionally docker installation.

## Preparing
When you first build you need the `build.properties` file so copy `build.properties.dist` to `build.properties`.
Fill the missing `build.version` (eg. 1.5.0) property and `build.release` (eg. stable).
Now install the build dependencies using `composer install` and `npm install` commands.

## Building installer
Do the changes you need to do, then run `composer build` command. If build process goes smoothly your installation
package should be ready in `.build` directory (eg `pkg_bpgallery_v1.5.0_stable.zip` file);

## Using provided docker image
The provided docker image is a testing environment. To run the image you need the docker installed (obviously :D).
Open the project directory in a console and run `composer test:server:start` to run the image.
Your testing Joomla! installation should be ready.
When you want to reset the image just run `composer test:server:cleanup`. This will remove all the changes you made and start new Joomla! installation.

- Project URL: http://localhost:8080
- PhpMyAdmin: http://localhost:8081
- Database Host: `mariadb`
- Database: `joomla`
- Database username: `joomla`
- Database password: `joomla`


## Changelog

### v1.0.1-beta2
- Added ability to group images by a category.
- Fixed subdirectory issue.
- Fixed thumbnail rebuild task.
- Added thumbnail quality setting.
- Added ability to set thumbnail size in both component and view parameters.
- Added ability to disable lightbox below provided resolution.
- Added missing admin menu links.
- Ability to add images to the existing queue.
- Fixed checkin translation
- Added ability to disable image title in a lightbox.
- Added a parameter to chose default image state after images upload.
- Added message informing user about the need to add images and select category.
- Added created of default category.
- Added loading of default component parameters.
- Fixing the package uninstall issue.


### v1.0.1
- First beta

### v1.0.0
- Pre-release