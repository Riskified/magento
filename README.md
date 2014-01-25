#magento
Riskified magento extension

### Deploying for testing from git on the server
- Login into the server Bitnami server with the magento.pem
- Clean up magento app and emove our scripts from magento, which is needed before testing instalations (If we add new files we need to modify the script)
 
```sh
  sh clean_up.sh
``` 
- Run the deploy_extension script which will clone from github and put all the files in the right places via rsync
(If we add new root section of code beyond js and app we need to modify the script)
 
```sh
  sh deploy_extension.sh
```

### Synhing up from local to server
```sh
rsync -azh app/  bitnami@ec2-50-17-5-41.compute-1.amazonaws.com:/opt/bitnami/apps/magento/htdocs/app/
```

