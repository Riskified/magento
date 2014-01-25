#magento
Riskified magento extension

##Login to machine
ssh -i ~/.ssh/magento.pem bitnami@magento.riskified.com

### Deploying for testing from git
in the home directory 
```sh
sh deploy_extension.sh
```
This will clone from guthub and put all the files in the right places via rsync
If we add new root section of code beyond js and app we need to modify the script
### Synhing up from local to server
```sh
rsync -azh app/  bitnami@ec2-50-17-5-41.compute-1.amazonaws.com:/opt/bitnami/apps/magento/htdocs/app/
```
### Cleaning up magento app from our code
in the home directory 
```sh
sh clean_up.sh
```
This will remove our scripts from magento, which is needed before testing instalations 
If we add new files we need to modify the script

