# How to contribute

Your contributions are welcome. There are several ways to help out:

* Create an issue on GitHub, if you have found a bug or have an idea for a feature
* Write test cases for open bug issues
* Write patches for open bug/feature issues

There are a few guidelines that you need to follow:

* The code must adhere to the [CakePHP coding standard](http://book.cakephp.org/2.0/en/contributing/cakephp-coding-conventions.html).
* All code changes should be covered by unit tests

## Issues

* Submit an issue, but:
  * Make sure it does not already exist
  * Clearly describe the issue including steps to reproduce, when it is a bug
  * Make sure you mention the version and configuration of all software that could be relevant

## Coding Standard

Make sure your code changes comply with the CakePHP Coding standard,
using [PHP Codesniffer](https://github.com/squizlabs/PHP_CodeSniffer).
Execute the following command from the plugin folder:

    vendor\bin\phpcs -p --extensions=php --standard=vendor/cakephp/cakephp-codesniffer/CakePHP Config Model Test

## Additional Resources

* [General GitHub documentation](https://help.github.com/)
* [GitHub pull request documentation](https://help.github.com/send-pull-requests/)
