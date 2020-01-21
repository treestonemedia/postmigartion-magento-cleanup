# Magento2 Post-migration Cleanup

When using the Magento data migration tool, if the store you're migrating from has a default attribute set, when the migration tool runs, it will create a new attribute set called 'Migration_Default'.
Within that new attribute set, it will create groups such as 'Migration_Prices'.
The default attribute set on the site will remain the initial Default attribute set.

This module is meant to be used post-migration, and it will do the following:

* Set the 'Migration_Default' attribute set as the default attribute set.
* Delete the initial 'Default' attribute set.
* Rename 'Migration_Default' attribute set to 'Default'.
* Delete empty attribute groups (this can happen if the store you're migrating from has groups and you ignored all the attributes in the group).
* Rename attribute groups from 'Migration_{groupName}' to '{groupName}'

# Installation

**Add the package to your composer.json**

`composer require treestone/postmigration`

**Enable and Install the Module**

`bin/magento module:enable Treestone_Postmigration`

# Usage

**This application, for now, consists of only a one-line CLI command**

`bin/magento treestone:postmigration:attributesets`

# Facts

* I only tested this on Magento 2.2.3
* This will delete the initial default attribute set - so use with caution
* This was developed for a specific migration that we did

# Support

If you encounter any issues - feel free to open an issue

# Contribution

Any contribution is welcome :)
