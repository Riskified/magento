#magento
Riskified magento extension

##Login to machine
ssh -i ~/.ssh/magento.pem bitnami@magento.riskified.com

##Deploying for testing from git
in the home directory 
<br>
<code>sh deploy_extension.sh </code>
<br>
This will clone from guthub and put all the files in the right places via rsync
If we add new root section of code beyond js and app we need to modify the script

##Cleaning up magento app from our code
in the home directory 
<br>
<code>sh clean_up.sh </code>
<br>
This will remove our scripts from magento, which is needed before testing instalations 
If we add new files we need to modify the script

