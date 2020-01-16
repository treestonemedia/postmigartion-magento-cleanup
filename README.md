# Magento2 Post migration Cleanup

When using the Magento data migration tool, if the store you're migrating from has a default attribute set, when the migration tool runs, it will create a new attribute set called 'Migration_Default'.
Within that new attribute set, it will create groups such as 'Migration_Prices'.
The default attribute set on teh site will remain the initial Default attribute set.

This module is meant to be used post migration, and it will do the following:

* Set the 'Migration_Default' attribute set as the default attribute set.
* Delete the initial 'Default' attribute set.
* Rename 'Migration_Default' attribute set to 'Default'.
* Delete empty attribute groups (this can happen if the store you're migrating from has groups and you ignored all the attributes in the group).
* Rename attribute groups from 'Migration_{groupName}' to '{groupName}'

# Instalation


# Facts

* I only tested this on Magento 2.2.3
* This will delete the initial deafult attribute set - so use with caution
* This was developed for a specific migration that we did

# Support

If you encounter any issues - fell free to open an issue

# Contribution

Any contribution is welcome :)
