#!/bin/bash
# On a local bitnami installation the directory should be like /Applications/magento-1.7.0.2-0/apps/magento/htdocs
MAGENTO_ROOT_DIR=$1
echo "Magento root dir is $MAGENTO_ROOT_DIR"

# Todo: make this script cleaner with a loop

# Clean the files in the installation
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
rm $1/skin/adminhtml/default/default/images/riskified/logo.jpg

# Create the directories
mkdir $1/app/code/community/Riskified
mkdir $1/app/code/community/Riskified/Full
mkdir $1/app/code/community/Riskified/Full/Block
mkdir $1/app/code/community/Riskified/Full/Block/Adminhtml
mkdir $1/app/code/community/Riskified/Full/controllers
mkdir $1/app/code/community/Riskified/Full/etc
mkdir $1/app/code/community/Riskified/Full/Helper
mkdir $1/app/code/community/Riskified/Full/Model
mkdir $1/app/code/community/Riskified/Full/controllers
mkdir $1/app/code/community/Riskified/Full/controllers/Adminhtml
mkdir $1/app/design/adminhtml
mkdir $1/app/design/adminhtml/default
mkdir $1/app/design/adminhtml/default/default
mkdir $1/app/design/adminhtml/default/default/template
mkdir $1/app/design/adminhtml/default/default/template/full
mkdir $1/app/design/frontend/default
mkdir $1/app/design/frontend/base/default
mkdir $1/app/design/frontend/base/default/template
mkdir $1/app/design/frontend/base/default/template/full
mkdir $1/js/riskified
mkdir $1/skin/adminhtml/default/default/images
mkdir $1/skin/adminhtml/default/default/images/riskified

# Copy the source files.
cp ./app/code/community/Riskified/Full/Block/Adminhtml/Grid.php                 $1/app/code/community/Riskified/Full/Block/Adminhtml/
cp ./app/code/community/Riskified/Full/Block/Adminhtml/View.php                 $1/app/code/community/Riskified/Full/Block/Adminhtml/
cp ./app/code/community/Riskified/Full/Block/Jsinit.php                         $1/app/code/community/Riskified/Full/Block/
cp ./app/code/community/Riskified/Full/controllers/Adminhtml/FullController.php $1/app/code/community/Riskified/Full/controllers/Adminhtml/
cp ./app/code/community/Riskified/Full/etc/config.xml                           $1/app/code/community/Riskified/Full/etc/
cp ./app/code/community/Riskified/Full/etc/system.xml                           $1/app/code/community/Riskified/Full/etc/
cp ./app/code/community/Riskified/Full/Helper/Data.php                          $1/app/code/community/Riskified/Full/Helper/
cp ./app/code/community/Riskified/Full/Model/Authorizenet.php                   $1/app/code/community/Riskified/Full/Model/
cp ./app/code/community/Riskified/Full/Model/Observer.php                       $1/app/code/community/Riskified/Full/Model/
cp ./app/design/adminhtml/default/default/layout/full.xml                       $1/app/design/adminhtml/default/default/layout/
cp ./app/design/adminhtml/default/default/template/full/jsinit.phtml            $1/app/design/adminhtml/default/default/template/full/
cp ./app/etc/modules/Riskified_Full.xml                                         $1/app/etc/modules/
cp ./js/riskified/full.js                                                       $1/js/riskified/
cp ./skin/adminhtml/default/default/images/riskified/logo.jpg                   $1/skin/adminhtml/default/default/images/riskified/
cp ./app/design/frontend/base/default/template/full/riskified.phtml             $1/app/design/frontend/base/default/template/full/
cp ./app/design/frontend/base/default/layout/full.xml                           $1/app/design/frontend/base/default/layout/

