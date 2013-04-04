echo "deleting old local repo" 
rm -rf magento 
git clone https://github.com/Riskified/magento.git
echo "synching with magento app" 
rsync -avs magento/js/ /opt/bitnami/apps/magento/htdocs/js/
rsync -avs magento/skin/ /opt/bitnami/apps/magento/htdocs/skin/
rsync -avs magento/app/ /opt/bitnami/apps/magento/htdocs/app/
