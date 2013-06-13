#!/bin/bash
# On a local bitnami installation the directory should be like /Applications/magento-1.7.0.2-0/apps/magento/htdocs
MAGENTO_ROOT_DIR=$1
echo "Magento root dir is $MAGENTO_ROOT_DIR"

# Todo: make this script cleaner with a loop

# Clean the files in the installation
rm -rf $1/app/design/adminhtml/default/default/layout/full.xml/*
rmdir $1/app/design/adminhtml/default/default/layout/full.xml
rm -rf $1/app/design/adminhtml/default/default/template/full/jsinit.phtml/*
rmdir $1/app/design/adminhtml/default/default/template/full/jsinit.phtml
rm -rf $1/app/etc/modules/Riskified_Full.xml/*
rmdir $1/app/etc/modules/Riskified_Full.xml
rm -rf $1/js/riskified/full.js/*
rmdir $1/js/riskified/full.js
rmdir $1/js/riskified
rm -rf $1/skin/adminhtml/default/default/images/riskified/logo.jpg/*
rmdir $1/skin/adminhtml/default/default/images/riskified/logo.jpg
rmdir $1/skin/adminhtml/default/default/images/riskified
rmdir $1/skin/adminhtml/default/default/images/riskified
rm -rf $1/app/design/frontend/base/default/template/full/riskified.phtml/*
rmdir $1/app/design/frontend/base/default/template/full/riskified.phtml
rm -rf $1/app/design/frontend/base/default/layout/full.xml/*
rmdir $1/app/design/frontend/base/default/layout/full.xml

rm $1/app/code/community/Riskified/Full/Block/Adminhtml/Grid.php
rm $1/app/code/community/Riskified/Full/Block/Adminhtml/View.php
rm $1/app/code/community/Riskified/Full/Block/Jsinit.php
rm $1/app/code/community/Riskified/Full/controllers/Adminhtml/FullController.php
rm $1/app/code/community/Riskified/Full/etc/config.xml
rm $1/app/code/community/Riskified/Full/etc/system.xml
rm $1/app/code/community/Riskified/Full/Helper/Data.php
rm $1/app/code/community/Riskified/Full/Model/Authorizenet.php
rm $1/app/code/community/Riskified/Full/Model/Observer.php
rm $1/app/design/adminhtml/default/default/layout/full.xml
rm $1/app/design/adminhtml/default/default/template/full/jsinit.phtml
rm $1/app/design/frontend/default/default/layout/full.xml
rm $1/app/design/frontend/default/default/template/full/full.phtml
rm $1/app/etc/modules/Riskified_Full.xml
rm $1/js/riskified/full.js
rmdir $1/js/riskified
rm $1/skin/adminhtml/default/default/images/riskified/logo.jpg
rm $1/app/design/frontend/base/default/template/full/riskified.phtml
rm $1/app/design/frontend/base/default/layout/full.xml

