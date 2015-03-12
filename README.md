# Advanced Features Values for Prestashop
This is a module for Prestashop 1.5.3+.
### Multiple feature values
This allows the selection of multiple values for product features, instead of just one. This is compatible with the default product page display, showing all selected values in the Features table, and with the blocklayered module, which will display all selected values in the facet navigation.
### Feature values ordering
This allows the ordering of the features values. This is compatible with the default product page display, showing all selected values in the Features table in the correct order.

### Install
Use the include *advancedfeaturesvalues.zip* file to install via the Modules page, or copy the *advancedfeaturesvalues* folder into the */modules* folder of your Prestashop installation.

### Known issues
Currently, the installation process fails with PHP version prior to 5.5. Still investigating… Help is welcome!

### Changelog
* 1.0.3: Correct ordering in the blocklayered filters after selecting a value and an Ajax request.
* 1.0.2: Makes the blocklayered filters reflect the features valures ordering. (PS 1.6.0.11+)
* 1.0.1: Clear caches on (un)install.
* 1.0.0: Initial commit.