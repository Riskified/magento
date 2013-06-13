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
#mkdir $1/app/design/frontend/default/default
#mkdir $1/app/design/frontend/default/default/template
#mkdir $1/app/design/frontend/default/default/template/full
mkdir $1/app/design/frontend/base/default
mkdir $1/app/design/frontend/base/default/template
mkdir $1/app/design/frontend/base/default/template/full
mkdir $1/js/riskified
mkdir $1/skin/adminhtml/default/default/images
mkdir $1/skin/adminhtml/default/default/images/riskified

# Copy the source files.
cp ./app/code/community/Riskified/Full/Block/Adminhtml/Grid.php                 $1/app/code/community/Riskified/Full/Block/Adminhtml/Grid.php
cp ./app/code/community/Riskified/Full/Block/Adminhtml/View.php                 $1/app/code/community/Riskified/Full/Block/Adminhtml/View.php
cp ./app/code/community/Riskified/Full/Block/Jsinit.php                         $1/app/code/community/Riskified/Full/Block/Jsinit.php
cp ./app/code/community/Riskified/Full/controllers/Adminhtml/FullController.php $1/app/code/community/Riskified/Full/controllers/Adminhtml/FullController.php
cp ./app/code/community/Riskified/Full/etc/config.xml                           $1/app/code/community/Riskified/Full/etc/config.xml
cp ./app/code/community/Riskified/Full/etc/system.xml                           $1/app/code/community/Riskified/Full/etc/system.xml
cp ./app/code/community/Riskified/Full/Helper/Data.php                          $1/app/code/community/Riskified/Full/Helper/Data.php
cp ./app/code/community/Riskified/Full/Model/Authorizenet.php                   $1/app/code/community/Riskified/Full/Model/Authorizenet.php
cp ./app/code/community/Riskified/Full/Model/Observer.php                       $1/app/code/community/Riskified/Full/Model/Observer.php
cp ./app/design/adminhtml/default/default/layout/full.xml                       $1/app/design/adminhtml/default/default/layout/full.xml
cp ./app/design/adminhtml/default/default/template/full/jsinit.phtml            $1/app/design/adminhtml/default/default/template/full/jsinit.phtml
cp ./app/etc/modules/Riskified_Full.xml                                         $1/app/etc/modules/Riskified_Full.xml
cp ./js/riskified/full.js                                                       $1/js/riskified/full.js
cp ./skin/adminhtml/default/default/images/riskified/logo.jpg                   $1/skin/adminhtml/default/default/images/riskified/logo.jpg
#cp ./app/design/frontend/default/default/template/full/full.phtml              $1/app/design/frontend/default/default/template/full/full.phtml
#cp ./app/design/frontend/default/default/layout/full.xml                       $1/app/design/frontend/default/default/layout/full.xml
cp ./app/design/frontend/base/default/template/full/riskified.phtml             $1/app/design/frontend/base/default/template/full/riskified.phtml
cp ./app/design/frontend/base/default/layout/full.xml                           $1/app/design/frontend/base/default/layout/full.xml
#cp ./riskified_magento.xml $1/riskified_magento.xml

# Files that need to be changed...
#
# Move the /app/design/frontend/default/default/layout/full.xml to the app/design/frontend/base/default/layout/ directory (like piwikanalytics.xml)
# the contents of the full.xml file should point to the correct javascript template file:
# Piwik: 
#app/design/frontend/base/default/layout/piwikanalytics.xml 
#<?xml version="1.0"?>
#<!--
# *
# * Piwik Extension for Magento created by Adrian Speyer
# * Get Piwik at http://www.piwik.org - Open source web analytics
# *
# * @category    design
# * @package     base_default_layout_piwikanalytics
# * @copyright   Copyright (c) 2012 Adrian Speyer. (http://www.adrianspeyer.com)
# * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
# *
# */
#
#-->
#<layout version="0.1.0">
#
#<!--
#Default layout, loads most of the pages
#-->
#
#    <default>
#        <!-- Mage_PiwikAnalytics -->
#        <reference name="before_body_end">
#            <block type="piwikanalytics/piwik" name="piwik_analytics" as="piwik_analytics" template="piwikanalytics/piwik.phtml" />
#        </reference>
#    </default>
#</layout>
#
# Remove the /js/riskified/full.js file
#
# Change the ./app/design/frontend/default/default/template/full/full.phtml to be like the piwik analytics code.              
